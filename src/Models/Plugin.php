<?php

namespace Siaoynli\PluginStore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plugin extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'package_name',
        'display_name',
        'slug',
        'description',
        'author',
        'homepage',
        'icon',
        'status',
        'install_type',
        'installed_version',
        'installed_path',
        'download_count',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'download_count' => 'integer',
    ];

    /**
     * 状态常量
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_PENDING = 'pending';

    /**
     * 安装方式常量
     */
    public const INSTALL_TYPE_COMPOSER = 'composer';
    public const INSTALL_TYPE_UPLOAD = 'upload';

    /**
     * 所属分类
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PluginCategory::class, 'category_id');
    }

    /**
     * 插件版本
     */
    public function versions(): HasMany
    {
        return $this->hasMany(PluginVersion::class);
    }

    /**
     * 最新版本
     */
    public function latestVersion()
    {
        return $this->hasOne(PluginVersion::class)->where('is_latest', true);
    }

    /**
     * 是否已安装
     */
    public function isInstalled(): bool
    {
        return $this->installed_version !== null
            && $this->installed_path !== null
            && is_dir($this->installed_path);
    }

    /**
     * 是否启用
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * 搜索范围 — 关键字
     */
    public function scopeSearch($query, ?string $keyword)
    {
        if (empty($keyword)) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword) {
            $q->where('display_name', 'like', "%{$keyword}%")
              ->orWhere('package_name', 'like', "%{$keyword}%")
              ->orWhere('description', 'like', "%{$keyword}%")
              ->orWhere('author', 'like', "%{$keyword}%");
        });
    }

    /**
     * 搜索范围 — 分类
     */
    public function scopeOfCategory($query, ?int $categoryId)
    {
        if ($categoryId === null) {
            return $query;
        }

        return $query->where('category_id', $categoryId);
    }

    /**
     * 搜索范围 — 状态
     */
    public function scopeOfStatus($query, ?string $status)
    {
        if ($status === null) {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * 递增下载计数
     */
    public function incrementDownloads(): void
    {
        $this->increment('download_count');
    }
}
