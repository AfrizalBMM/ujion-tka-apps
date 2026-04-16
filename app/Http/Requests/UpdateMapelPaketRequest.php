<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMapelPaketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jumlah_soal' => 'required|integer|min:1|max:200',
            'durasi_menit' => 'required|integer|min:1|max:600',
            'urutan' => 'required|integer|min:1|max:10',
        ];
    }
}

