<?php

namespace Siaoynli\PluginStore\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PluginResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => new PluginCategoryResource($this->category)),
            'package_name' => $this->package_name,
            'display_name' => $this->display_name,
            'slug' => $this->slug,
            'description' => $this->description,
            'author' => $this->author,
            'homepage' => $this->homepage,
            'icon' => $this->icon,
            'status' => $this->status,
            'install_type' => $this->install_type,
            'installed_version' => $this->installed_version,
            'installed_path' => $this->installed_path,
            'download_count' => $this->download_count,
            'settings' => $this->settings,
            'is_installed' => $this->isInstalled(),
            'is_active' => $this->isActive(),
            'latest_version' => $this->whenLoaded('latestVersion', fn () => new PluginVersionResource($this->latestVersion)),
            'versions' => PluginVersionResource::collection($this->whenLoaded('versions')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
