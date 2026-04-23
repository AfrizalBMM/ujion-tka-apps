<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mapel = $this->route('mapel');
        $isSurvey = $mapel?->isSurvey() ?? false;
        $maxNomorSoal = (int) ($mapel?->jumlah_soal ?? 30);
        $maxNomorSoal = max(1, min(200, $maxNomorSoal));

        $teksBacaanRule = Rule::exists('teks_bacaans', 'id');
        if ($mapel?->id) {
            $teksBacaanRule = $teksBacaanRule->where('mapel_paket_id', (int) $mapel->id);
        }

        return [
            'nomor_soal' => ['required', 'integer', 'min:1', 'max:' . $maxNomorSoal],
            'tipe_soal' => ['required', Rule::in($isSurvey ? ['pilihan_ganda'] : ['pilihan_ganda', 'menjodohkan'])],
            'indikator' => 'required|string',
            'dimensi' => [$isSurvey ? 'required' : 'nullable', 'string', 'max:255'],
            'subdimensi' => 'nullable|string|max:255',
            'kategori_profil' => 'nullable|string|max:255',
            'arah_skor' => [$isSurvey ? 'required' : 'nullable', Rule::in(['positif', 'negatif'])],
            'pertanyaan' => 'required|string',
            'gambar' => 'nullable|image|max:2048',
            'teks_bacaan_id' => ['nullable', $teksBacaanRule],
            'bobot' => 'nullable|integer|min:1|max:100',
            'pilihan' => 'exclude_unless:tipe_soal,pilihan_ganda|required|array|size:4',
            'pilihan.*.kode' => 'exclude_unless:tipe_soal,pilihan_ganda|required|in:A,B,C,D',
            'pilihan.*.teks' => 'exclude_unless:tipe_soal,pilihan_ganda|required|string',
            'pilihan.*.nilai_survey' => [$isSurvey ? 'required' : 'nullable', 'integer', 'min:1', 'max:4'],
            'pilihan.*.profil_label' => 'nullable|string|max:255',
            'pilihan_gambar.*' => 'nullable|image|max:2048',
            'jawaban_benar' => $isSurvey
                ? 'nullable|in:A,B,C,D'
                : 'exclude_unless:tipe_soal,pilihan_ganda|required|in:A,B,C,D',
            'pasangan' => 'exclude_unless:tipe_soal,menjodohkan|required|array|min:3',
            'pasangan.*.teks_kiri' => 'exclude_unless:tipe_soal,menjodohkan|required|string',
            'pasangan.*.teks_kanan' => 'exclude_unless:tipe_soal,menjodohkan|required|string',
        ];
    }
}
