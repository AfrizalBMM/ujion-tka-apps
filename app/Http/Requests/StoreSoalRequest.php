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
            'pilihan' => 'required_if:tipe_soal,pilihan_ganda|array|size:4',
            'pilihan.*.kode' => 'required_if:tipe_soal,pilihan_ganda|in:A,B,C,D',
            'pilihan.*.teks' => 'required_if:tipe_soal,pilihan_ganda|string',
            'pilihan_gambar.*' => 'nullable|image|max:2048',
            'jawaban_benar' => 'required_if:tipe_soal,pilihan_ganda|in:A,B,C,D',
            'pasangan' => 'required_if:tipe_soal,menjodohkan|array|min:3',
            'pasangan.*.teks_kiri' => 'required_if:tipe_soal,menjodohkan|string',
            'pasangan.*.teks_kanan' => 'required_if:tipe_soal,menjodohkan|string',
        ];
    }
}
