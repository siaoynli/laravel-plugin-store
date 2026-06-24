<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 插件市场配置
    |--------------------------------------------------------------------------
    */

    // 插件是否启用
    'enabled' => env('PLUGIN_STORE_ENABLED', true),

    // 路由前缀
    'route_prefix' => 'plugin-store',

    // 中间件
    'middleware' => ['api'],

    // 上传配置
    'upload' => [
        // 上传文件最大大小 (MB)
        'max_size' => env('PLUGIN_STORE_MAX_UPLOAD_SIZE', 50),

        // 允许的 MIME 类型
        'allowed_mimes' => ['application/zip', 'application/x-zip-compressed'],

        // 上传临时存储路径
        'temp_path' => storage_path('app/plugin-store/uploads'),

        // 解压目标路径（相对于 base_path）
        'packages_path' => 'packages',
    ],

    // Composer 配置
    'composer' => [
        // composer 可执行文件路径
        'binary' => env('COMPOSER_BINARY', 'composer'),

        // 执行超时时间（秒）
        'timeout' => env('COMPOSER_TIMEOUT', 300),

        // 项目根目录（默认为 base_path）
        'working_dir' => null,
    ],

    // 插件发现
    'discovery' => [
        // 自动扫描已安装插件并同步到数据库
        'auto_sync' => env('PLUGIN_STORE_AUTO_SYNC', true),
    ],
];
