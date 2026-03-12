<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBidRequest extends FormRequest
{
    public function authorize(): bool
    {
        $bid = $this->route('bid');
        return $this->user() && $bid && $bid->user_id === $this->user()->id && $bid->status === 'pending';
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
