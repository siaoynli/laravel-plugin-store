<?php

namespace Siaoynli\PluginStore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PluginCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * 分类下的插件
     */
    public function plugins(): HasMany
    {
        return $this->hasMany(Plugin::class, 'category_id');
    }

    /**
     * 获取分类下的插件数量
     */
    public function getPluginsCountAttribute(): int
    {
        return $this->plugins()->count();
    }

    /**
     * 按排序权重排序
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
