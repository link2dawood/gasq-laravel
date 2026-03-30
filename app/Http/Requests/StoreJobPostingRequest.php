<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobPostingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'latitude' => $this->input('latitude') === '' || $this->input('latitude') === null
                ? null
                : $this->input('latitude'),
            'longitude' => $this->input('longitude') === '' || $this->input('longitude') === null
                ? null
                : $this->input('longitude'),
            'google_place_id' => $this->input('google_place_id') === '' || $this->input('google_place_id') === null
                ? null
                : $this->input('google_place_id'),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->isBuyer() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'service_start_date' => ['nullable', 'date'],
            'service_end_date' => ['nullable', 'date', 'after_or_equal:service_start_date'],
            'guards_per_shift' => ['nullable', 'integer', 'min:1', 'max:255'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'gte:budget_min'],
            'description' => ['nullable', 'string'],
            'property_type' => ['nullable', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
