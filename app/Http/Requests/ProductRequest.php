<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($productId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'unit' => ['required', 'string', 'max:20'],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'sale_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:1000000'],
            'location' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'opening_stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * Columns only. Keys the validator drops when absent are filled in explicitly
     * so a partially submitted form can never hand a missing index to the model.
     */
    public function productAttributes(): array
    {
        $data = $this->validated();

        return [
            'sku' => $data['sku'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'unit' => $data['unit'],
            'cost_price' => $data['cost_price'],
            'sale_price' => $data['sale_price'],
            'reorder_level' => $data['reorder_level'],
            'location' => $data['location'] ?? null,
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
