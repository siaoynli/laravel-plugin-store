<?php

namespace Siaoynli\PluginStore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstallPluginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plugin_id' => ['required', 'integer', 'exists:plugins,id'],
            'version' => ['nullable', 'string', 'max:50'],
        ];
    }
}
