<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaketSoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenjang_id' => 'required|exists:jenjangs,id',
            'assessment_type' => 'nullable|in:paket_lengkap,tka,survey_karakter,sulingjar',
            'nama' => 'required|string|max:255',
            'tahun_ajaran' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'is_active' => 'nullable|boolean',
        ];
    }
}
