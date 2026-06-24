<?php

namespace Siaoynli\PluginStore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePluginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:plugin_categories,id'],
            'package_name' => ['required', 'string', 'max:255', 'unique:plugins,package_name'],
            'display_name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:plugins,slug', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:2000'],
            'author' => ['nullable', 'string', 'max:100'],
            'homepage' => ['nullable', 'url', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,inactive,pending'],
            'install_type' => ['nullable', 'string', 'in:composer,upload'],
            'settings' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // 自动生成 slug
        if (empty($this->slug) && !empty($this->package_name)) {
            $this->merge([
                'slug' => str_replace('/', '-', $this->package_name),
            ]);
        }
    }
}
