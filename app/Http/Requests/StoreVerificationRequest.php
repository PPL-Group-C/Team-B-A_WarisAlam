<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // "auth" middleware + the controller's own-report check
    }

    public function rules(): array
    {
        return [
            'verdict' => ['required', 'in:confirm,dispute'],
            'comment' => ['required_if:verdict,dispute', 'nullable', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'verdict.required' => 'Pilih konfirmasi atau sanggah.',
            'verdict.in' => 'Pilihan tidak valid.',
            'comment.required_if' => 'Tulis alasan sanggahan (minimal 10 karakter).',
            'comment.min' => 'Tulis alasan sanggahan (minimal 10 karakter).',
            'comment.max' => 'Komentar maksimal 500 karakter.',
        ];
    }
}
