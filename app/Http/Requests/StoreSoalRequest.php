<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nomor_soal' => 'required|integer|min:1|max:30',
            'tipe_soal' => 'required|in:pilihan_ganda,menjodohkan',
            'indikator' => 'required|string',
            'pertanyaan' => 'required|string',
            'gambar' => 'nullable|image|max:2048',
            'teks_bacaan_id' => 'nullable|exists:teks_bacaans,id',
            'bobot' => 'nullable|integer|min:1|max:100',
            'pilihan' => 'exclude_unless:tipe_soal,pilihan_ganda|required|array|size:4',
            'pilihan.*.kode' => 'exclude_unless:tipe_soal,pilihan_ganda|required|in:A,B,C,D',
            'pilihan.*.teks' => 'exclude_unless:tipe_soal,pilihan_ganda|required|string',
            'pilihan_gambar.*' => 'nullable|image|max:2048',
            'jawaban_benar' => 'exclude_unless:tipe_soal,pilihan_ganda|required|in:A,B,C,D',
            'pasangan' => 'exclude_unless:tipe_soal,menjodohkan|required|array|min:3',
            'pasangan.*.teks_kiri' => 'exclude_unless:tipe_soal,menjodohkan|required|string',
            'pasangan.*.teks_kanan' => 'exclude_unless:tipe_soal,menjodohkan|required|string',
        ];
    }
}
