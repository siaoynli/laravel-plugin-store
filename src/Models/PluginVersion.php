<?php

namespace Siaoynli\PluginStore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginVersion extends Model
{
    protected $fillable = [
        'plugin_id',
        'version',
        'changelog',
        'file_path',
        'file_size',
        'min_php_version',
        'min_laravel_version',
        'download_count',
        'is_latest',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'download_count' => 'integer',
        'is_latest' => 'boolean',
    ];

    /**
     * 关联插件
     */
    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    /**
     * 标记为最新版本
     */
    public function markAsLatest(): void
    {
        // 先将同插件其他版本标记为非最新
        self::where('plugin_id', $this->plugin_id)
            ->where('id', '!=', $this->id)
            ->update(['is_latest' => false]);

        $this->update(['is_latest' => true]);
    }

    /**
     * 递增下载计数
     */
    public function incrementDownloads(): void
    {
        $this->increment('download_count');
    }

    /**
     * 获取格式化后的文件大小
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
