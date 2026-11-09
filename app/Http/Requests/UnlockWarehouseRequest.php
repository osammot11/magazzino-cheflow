<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnlockWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['password' => ['required', 'string', 'max:256']];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Inserisci la password per entrare.',
            'password.string' => 'Inserisci una password valida.',
            'password.max' => 'Inserisci una password valida.',
        ];
    }
}
