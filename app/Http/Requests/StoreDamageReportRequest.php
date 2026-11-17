<?php

namespace App\Http\Requests;

use App\Enums\DamageCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDamageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the route is already behind the "auth" middleware
    }

    public function isDraft(): bool
    {
        return $this->input('intent') === 'draft';
    }

    public function rules(): array
    {
        // A draft only needs a site; everything else is enforced on final submit.
        $need = $this->isDraft() ? 'nullable' : 'required';

        return [
            'heritage_site_id' => ['required', 'exists:heritage_sites,id'],
            'latitude' => [$need, 'numeric', 'between:-90,90'],
            'longitude' => [$need, 'numeric', 'between:-180,180'],
            'gps_accuracy_m' => ['nullable', 'integer', 'min:0'],
            'location_source' => ['nullable', 'in:site,exif,gps,manual'],

            'photos' => [$need, 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:10240'], // 10 MB, in KB

            'category' => [$need, Rule::enum(DamageCategory::class)],
            'severity' => [$need, 'integer', 'between:1,5'],

            'description' => [$need, 'string', 'max:1000', ...($this->isDraft() ? [] : ['min:20'])],

            'is_anonymous' => ['boolean'],
            'declaration' => [$this->isDraft() ? 'nullable' : 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'heritage_site_id.required' => 'Pilih situs cagar budaya terlebih dahulu.',
            'latitude.required' => 'Tentukan titik lokasi kerusakan di peta.',
            'photos.required' => 'Unggah minimal 1 foto bukti kerusakan.',
            'photos.max' => 'Maksimal 5 foto bukti.',
            'photos.*.image' => 'Berkas harus berupa foto.',
            'photos.*.mimes' => 'Format foto harus JPG atau PNG.',
            'photos.*.max' => 'Ukuran tiap foto maksimal 10 MB.',
            'category.required' => 'Pilih kategori ancaman.',
            'severity.required' => 'Pilih tingkat keparahan.',
            'description.required' => 'Isi kronologi dan rincian temuan.',
            'description.min' => 'Kronologi minimal 20 karakter.',
            'declaration.accepted' => 'Centang pernyataan kebenaran data laporan.',
        ];
    }
}
