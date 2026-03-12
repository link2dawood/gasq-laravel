<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isVendor() ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'message' => ['nullable', 'string', 'max:2000'],
            'proposal' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
