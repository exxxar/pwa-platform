<?php

namespace App\Http\Requests\Admin\TenantData;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'images' => 'nullable|array',
            'images.*' => 'string|max:255',
            'dimensions' => 'nullable|array',
            'delivery_terms' => 'nullable|string|max:1000',
            'external_source' => 'nullable|string|max:100',
            'external_id' => 'nullable|string|max:100',
            'config' => 'nullable|array',
            'is_active' => 'boolean',
            'not_for_delivery' => 'boolean',
            'in_stop_list' => 'boolean',
            'is_composite' => 'boolean',
            'is_weight_product' => 'boolean',
            'order_position' => 'nullable|integer|min:0',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Название товара обязательно',
            'price.required' => 'Цена обязательна',
            'price.min' => 'Цена не может быть отрицательной',
        ];
    }
}
