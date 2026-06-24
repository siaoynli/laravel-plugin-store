<?php

namespace Siaoynli\PluginStore\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PluginVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plugin_id' => $this->plugin_id,
            'version' => $this->version,
            'changelog' => $this->changelog,
            'file_path' => $this->file_path,
            'file_size' => $this->file_size,
            'formatted_file_size' => $this->formatted_file_size,
            'min_php_version' => $this->min_php_version,
            'min_laravel_version' => $this->min_laravel_version,
            'download_count' => $this->download_count,
            'is_latest' => $this->is_latest,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
