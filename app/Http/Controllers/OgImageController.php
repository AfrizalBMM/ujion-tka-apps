<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class OgImageController
{
    public function __invoke(): Response
    {
        $width = 1200;
        $height = 630;

        if (! function_exists('imagecreatetruecolor')) {
            abort(500, 'GD extension is required to generate OG image.');
        }

        $canvas = imagecreatetruecolor($width, $height);

        // Background: clean white with a subtle top/bottom contrast.
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);

        $topShade = imagecolorallocate($canvas, 245, 247, 255);
        imagefilledrectangle($canvas, 0, 0, $width, (int) ($height * 0.45), $topShade);

        // Drop a soft border.
        $border = imagecolorallocate($canvas, 226, 232, 240);
        imagerectangle($canvas, 0, 0, $width - 1, $height - 1, $border);

        // Place logo in the center.
        $logoPath = public_path('assets/img/logo.png');
        if (is_file($logoPath)) {
            $logo = @imagecreatefrompng($logoPath);
            if ($logo !== false) {
                imagesavealpha($logo, true);

                $srcW = imagesx($logo);
                $srcH = imagesy($logo);

                // Fit logo into a box of 520x520 while preserving aspect ratio.
                $maxW = 520;
                $maxH = 520;

                $scale = min($maxW / max(1, $srcW), $maxH / max(1, $srcH));
                $dstW = (int) max(1, round($srcW * $scale));
                $dstH = (int) max(1, round($srcH * $scale));

                $dstX = (int) round(($width - $dstW) / 2);
                $dstY = (int) round(($height - $dstH) / 2) - 10;

                imagealphablending($canvas, true);
                imagecopyresampled($canvas, $logo, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);

                imagedestroy($logo);
            }
        }

        // Add a small brand line at the bottom (built-in font).
        $text = config('app.name', 'Ujion TKA');
        $textColor = imagecolorallocate($canvas, 15, 23, 42); // slate-ish
        $font = 5; // built-in GD font
        $textW = imagefontwidth($font) * strlen($text);
        $textH = imagefontheight($font);
        $textX = (int) max(24, round(($width - $textW) / 2));
        $textY = $height - 40 - $textH;
        imagestring($canvas, $font, $textX, $textY, $text, $textColor);

        ob_start();
        imagepng($canvas, null, 8);
        $png = ob_get_clean();

        imagedestroy($canvas);

        return response($png, 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
