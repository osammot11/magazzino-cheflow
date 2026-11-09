<?php

namespace App\Http\Requests;

use App\Models\Variant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000'],
            'category' => ['nullable', 'string', 'max:255'], 'vendor' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'archived'])], 'variants' => ['required', 'array', 'min:1', 'max:100'],
            'variants.*.id' => ['nullable', 'integer', 'distinct'], 'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.sku' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'], 'variants.*.price' => ['required', 'numeric', 'min:0', 'max:9999999', 'decimal:0,2'],
            'variants.*.low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000000'],
            'variants.*.initial_quantity' => ['nullable', 'integer', 'min:0', 'max:100000000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['variants' => collect($this->input('variants', []))->map(function ($row) {
            if (is_array($row)) {
                $row['sku'] = mb_strtoupper(trim($row['sku'] ?? ''));
            }

            return $row;
        })->all()]);
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $product = $this->route('product');
            $existing = $product ? $product->variants()->pluck('id')->all() : [];
            $submitted = [];
            foreach ($this->input('variants', []) as $index => $row) {
                $id = $row['id'] ?? null;
                if ($id && ! in_array((int) $id, $existing, true)) {
                    $validator->errors()->add("variants.$index.id", 'Variante non appartenente al prodotto.');
                }
                if ($id) {
                    $submitted[] = (int) $id;
                }
                if (Variant::query()->where('sku', $row['sku'])->when($id, fn ($query) => $query->where('id', '!=', $id))->exists()) {
                    $validator->errors()->add("variants.$index.sku", 'Questo SKU è già utilizzato.');
                }
            }
            if (array_diff($existing, $submitted)) {
                $validator->errors()->add('variants', 'Le varianti esistenti devono essere conservate per mantenere lo storico.');
            }
        }];
    }
}
