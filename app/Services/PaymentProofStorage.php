<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentProofStorage
{
    private const DIRECTORY = 'payment-proofs';
    private const MAX_SIDE = 1600;
    private const JPEG_QUALITY = 82;

    public function store(UploadedFile $file): string
    {
        $sourcePath = $file->getRealPath();
        if (! $sourcePath) {
            throw new RuntimeException('File bukti pembayaran tidak bisa dibaca.');
        }

        $info = @getimagesize($sourcePath);
        if (! is_array($info)) {
            throw new RuntimeException('File bukti pembayaran harus berupa gambar.');
        }

        [$width, $height, $type] = $info;
        $source = $this->createImage($sourcePath, $type);
        if (! $source) {
            throw new RuntimeException('Format gambar bukti pembayaran tidak didukung.');
        }

        if ($type === IMAGETYPE_JPEG) {
            $source = $this->applyJpegOrientation($source, $sourcePath);
            $width = imagesx($source);
            $height = imagesy($source);
        }

        [$targetWidth, $targetHeight] = $this->targetSize($width, $height);
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        Storage::disk('public')->makeDirectory(self::DIRECTORY);

        $path = self::DIRECTORY . '/' . now()->format('YmdHis') . '-' . Str::uuid() . '.jpg';
        $absolutePath = Storage::disk('public')->path($path);

        if (! imagejpeg($target, $absolutePath, self::JPEG_QUALITY)) {
            imagedestroy($source);
            imagedestroy($target);

            throw new RuntimeException('Gagal menyimpan bukti pembayaran.');
        }

        imagedestroy($source);
        imagedestroy($target);

        return $path;
    }

    public function deleteOldProofs(iterable $paths, ?string $currentPath = null): void
    {
        foreach ($paths as $path) {
            if (! is_string($path) || $path === '' || $path === $currentPath) {
                continue;
            }

            Storage::disk('public')->delete($path);
        }
    }

    private function createImage(string $path, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    private function targetSize(int $width, int $height): array
    {
        $longestSide = max($width, $height);
        if ($longestSide <= self::MAX_SIDE) {
            return [$width, $height];
        }

        $ratio = self::MAX_SIDE / $longestSide;

        return [
            max(1, (int) round($width * $ratio)),
            max(1, (int) round($height * $ratio)),
        ];
    }

    private function applyJpegOrientation($image, string $path)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated ?: $image;
    }
}
