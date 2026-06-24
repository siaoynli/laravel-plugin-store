<?php

namespace Siaoynli\PluginStore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePluginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pluginId = $this->route('id');

        return [
            'category_id' => ['nullable', 'integer', 'exists:plugin_categories,id'],
            'package_name' => ['sometimes', 'string', 'max:255', 'unique:plugins,package_name,' . $pluginId],
            'display_name' => ['sometimes', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:100', 'unique:plugins,slug,' . $pluginId, 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:2000'],
            'author' => ['nullable', 'string', 'max:100'],
            'homepage' => ['nullable', 'url', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,inactive,pending'],
            'install_type' => ['nullable', 'string', 'in:composer,upload'],
            'installed_version' => ['nullable', 'string', 'max:50'],
            'installed_path' => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
