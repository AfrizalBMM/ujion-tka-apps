<?php

namespace App\Http\Controllers\Concerns;

use App\Models\MapelPaket;
use App\Models\PilihanJawaban;
use App\Models\Soal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

trait ManagesSoalCrud
{
    protected function persistSoal(Request $request, MapelPaket $mapel, ?Soal $soal = null): Soal
    {
        $nomorSoal = (int) $request->integer('nomor_soal');
        $bobot = (int) ($request->integer('bobot') ?: 1);
        $tipeSoal = $request->string('tipe_soal')->toString();

        $exists = Soal::query()
            ->where('mapel_paket_id', $mapel->id)
            ->where('nomor_soal', $nomorSoal)
            ->when($soal, fn ($query) => $query->where('id', '!=', $soal->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'nomor_soal' => 'Nomor soal sudah digunakan pada mapel ini.',
            ]);
        }

        if (! $soal && $mapel->soals()->count() >= $mapel->jumlah_soal) {
            throw ValidationException::withMessages([
                'nomor_soal' => 'Jumlah soal pada mapel ini sudah mencapai batas maksimum.',
            ]);
        }

        return DB::transaction(function () use ($request, $mapel, $soal, $nomorSoal, $bobot, $tipeSoal) {
            $payload = [
                'mapel_paket_id' => $mapel->id,
                'teks_bacaan_id' => $request->filled('teks_bacaan_id') ? $request->integer('teks_bacaan_id') : null,
                'nomor_soal' => $nomorSoal,
                'tipe_soal' => $tipeSoal,
                'indikator' => $request->string('indikator')->toString(),
                'pertanyaan' => $request->string('pertanyaan')->toString(),
                'bobot' => $bobot,
            ];

            if ($request->hasFile('gambar')) {
                if ($soal?->gambar) {
                    Storage::disk('public')->delete($soal->gambar);
                }

                $payload['gambar'] = $request->file('gambar')->store('soal', 'public');
            }

            $soal = $soal
                ? tap($soal, fn (Soal $item) => $item->update($payload))
                : Soal::create($payload);

            if ($tipeSoal === 'pilihan_ganda') {
                $this->syncPilihanJawaban($request, $soal);
                $this->cleanupMatching($soal);
            } else {
                $this->syncPasanganMenjodohkan($request, $soal);
                $this->cleanupChoices($soal);
            }

            return $soal->fresh(['pilihanJawabans', 'pasanganMenjodohkans', 'teksBacaan']);
        });
    }

    protected function deleteSoalAssets(Soal $soal): void
    {
        if ($soal->gambar) {
            Storage::disk('public')->delete($soal->gambar);
        }

        foreach ($soal->pilihanJawabans as $pilihan) {
            if ($pilihan->gambar) {
                Storage::disk('public')->delete($pilihan->gambar);
            }
        }
    }

    private function syncPilihanJawaban(Request $request, Soal $soal): void
    {
        $pilihan = collect($request->input('pilihan', []))
            ->map(fn (array $item) => [
                'kode' => $item['kode'] ?? null,
                'teks' => $item['teks'] ?? null,
            ])
            ->sortBy('kode')
            ->values();

        if ($pilihan->pluck('kode')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'pilihan' => 'Kode pilihan harus unik.',
            ]);
        }

        if (! $pilihan->pluck('kode')->diff(['A', 'B', 'C', 'D'])->isEmpty()) {
            throw ValidationException::withMessages([
                'pilihan' => 'Pilihan wajib terdiri dari A, B, C, dan D.',
            ]);
        }

        $jawabanBenar = $request->string('jawaban_benar')->toString();

        foreach ($pilihan as $item) {
            $image = $request->file("pilihan_gambar.{$item['kode']}");
            $record = PilihanJawaban::firstOrNew([
                'soal_id' => $soal->id,
                'kode' => $item['kode'],
            ]);

            if ($image) {
                if ($record->gambar) {
                    Storage::disk('public')->delete($record->gambar);
                }

                $record->gambar = $image->store('soal/pilihan', 'public');
            }

            $record->teks = $item['teks'];
            $record->is_benar = $item['kode'] === $jawabanBenar;
            $record->save();
        }
    }

    private function syncPasanganMenjodohkan(Request $request, Soal $soal): void
    {
        $soal->pasanganMenjodohkans()->delete();

        foreach (array_values($request->input('pasangan', [])) as $index => $pasangan) {
            $soal->pasanganMenjodohkans()->create([
                'teks_kiri' => $pasangan['teks_kiri'],
                'teks_kanan' => $pasangan['teks_kanan'],
                'urutan' => $index + 1,
            ]);
        }
    }

    private function cleanupChoices(Soal $soal): void
    {
        foreach ($soal->pilihanJawabans as $pilihan) {
            if ($pilihan->gambar) {
                Storage::disk('public')->delete($pilihan->gambar);
            }
        }

        $soal->pilihanJawabans()->delete();
    }

    private function cleanupMatching(Soal $soal): void
    {
        $soal->pasanganMenjodohkans()->delete();
    }
}
