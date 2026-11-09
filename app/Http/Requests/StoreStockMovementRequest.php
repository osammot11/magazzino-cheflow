<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['variant_id' => ['required', 'integer', 'exists:variants,id'], 'type' => ['required', Rule::in(['in', 'out'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000000'], 'reason' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:2000']];
    }
}
