# 📦 siaoynli/plugin-store

基于 [siaoynli/laravel-plugins](https://github.com/siaoynli/laravel-plugins) 插件系统的 **插件市场/仓库管理包**。提供插件的增删改查、Composer 安装、Zip 上传安装、卸载、启用/禁用等完整管理能力。前端为内嵌 Vue3 SPA。

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-%5E8.2-777BB4.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-%5E11.0-FF2D20.svg)](https://laravel.com)

## ✨ 功能特性

- **插件 CRUD** — 完整的增删改查，支持搜索、分类筛选、分页
- **Composer 安装** — 输入包名 + 版本号，自动执行 `composer require`
- **Zip 上传安装** — 上传 zip 包，自动解压到 `packages/` 目录
- **卸载/启用/禁用** — 插件完整生命周期管理
- **同步扫描** — 自动扫描磁盘已安装插件，同步到数据库
- **分类管理** — 插件分类 CRUD + 排序
- **版本管理** — 版本记录 + 最新版本标记 + 更新日志
- **现代化 UI** — Vue 3 + TypeScript + Naive UI 构建的管理界面

## 🚀 快速开始

### 1. 安装

```bash
composer require siaoynli/plugin-store
```

### 2. 运行迁移

```bash
php artisan migrate
```

### 3. 发布静态资源

```bash
php artisan vendor:publish --provider="Siaoynli\PluginStore\PluginStorePlugin"
```

> 构建产物会发布到 `public/plugins/plugin-store/` 目录。

### 4. 访问管理界面

浏览器打开 `/plugin-store/` 即可进入插件市场管理后台。

## 📋 系统要求

| 依赖 | 版本 |
|------|------|
| PHP | ^8.2 |
| Laravel | ^11.0 |
| siaoynli/laravel-plugins | ^1.0 |
| ext-zip | * |

## 📁 项目结构

```
plugin-store/
├── composer.json                        # 包声明
├── config/plugin.php                    # 插件配置
├── src/
│   ├── PluginStorePlugin.php            # 插件主类
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── PluginController.php     # 插件 CRUD
│   │   │   ├── PluginInstallController.php  # 安装/卸载/上传/同步
│   │   │   └── CategoryController.php   # 分类管理
│   │   ├── Requests/                    # 表单验证 (3 个)
│   │   └── Resources/                   # API 格式化 (3 个)
│   ├── Models/
│   │   ├── Plugin.php                   # 插件模型 (软删除)
│   │   ├── PluginVersion.php            # 版本模型
│   │   └── PluginCategory.php           # 分类模型
│   └── Services/
│       ├── PluginMarketplaceService.php # 核心业务逻辑
│       ├── ComposerInstallerService.php # Composer 安装
│       └── ZipUploadService.php         # Zip 上传解压
├── database/migrations/                 # 3 个迁移文件
├── routes/
│   ├── api.php                          # API 路由 (/api/plugin-store/*)
│   └── web.php                          # SPA catch-all (/plugin-store/*)
├── resources/
│   ├── views/app.blade.php              # SPA 入口 Blade 模板
│   └── frontend/                        # Vue 3 前端
│       └── src/
│           ├── pages/                   # 页面组件 (4 个)
│           ├── components/              # 可复用组件 (2 个)
│           └── api/                     # API 封装 + TS 类型
└── public/
    └── assets/                          # Vite 构建产物
```

## 🔌 API 接口

| 方法 | 路径 | 说明 |
|------|------|------|
| `GET` | `/api/plugin-store/plugins` | 插件列表（搜索/筛选/分页） |
| `GET` | `/api/plugin-store/plugins/{id}` | 插件详情 |
| `POST` | `/api/plugin-store/plugins` | 创建插件 |
| `PUT` | `/api/plugin-store/plugins/{id}` | 更新插件 |
| `DELETE` | `/api/plugin-store/plugins/{id}` | 删除插件（软删除） |
| `PATCH` | `/api/plugin-store/plugins/{id}/toggle` | 启用/禁用切换 |
| `POST` | `/api/plugin-store/install` | Composer 安装 |
| `POST` | `/api/plugin-store/upload` | Zip 上传安装 |
| `POST` | `/api/plugin-store/uninstall/{id}` | 卸载插件 |
| `POST` | `/api/plugin-store/refresh` | 同步已安装插件 |
| `GET` | `/api/plugin-store/categories` | 分类列表 |
| `POST` | `/api/plugin-store/categories` | 创建分类 |
| `PUT` | `/api/plugin-store/categories/{id}` | 更新分类 |
| `DELETE` | `/api/plugin-store/categories/{id}` | 删除分类 |

## ⚙️ 配置

发布配置文件：

```bash
php artisan vendor:publish --provider="Siaoynli\PluginStore\PluginStorePlugin"
```

配置文件位于 `config/plugins/siaoynli-plugin-store.php`：

```php
return [
    // 插件是否启用
    'enabled' => env('PLUGIN_STORE_ENABLED', true),

    // 路由前缀
    'route_prefix' => 'plugin-store',

    // API 中间件
    'middleware' => ['api'],

    // 上传配置
    'upload' => [
        'max_size' => env('PLUGIN_STORE_MAX_UPLOAD_SIZE', 50),  // MB
        'allowed_mimes' => ['application/zip', 'application/x-zip-compressed'],
        'temp_path' => storage_path('app/plugin-store/uploads'),
        'packages_path' => 'packages',  // 解压目标（相对于 base_path）
    ],

    // Composer 配置
    'composer' => [
        'binary' => env('COMPOSER_BINARY', 'composer'),
        'timeout' => env('COMPOSER_TIMEOUT', 300),  // 秒
        'working_dir' => null,  // 默认 base_path
    ],

    // 自动发现
    'discovery' => [
        'auto_sync' => env('PLUGIN_STORE_AUTO_SYNC', true),
    ],
];
```

## 🛠️ 本地开发

### 后端

```bash
# 在 Laravel 项目中添加 path 仓库
composer config repositories.plugin-store path ./packages/siaoynli/plugin-store

# 安装本地包
composer require siaoynli/plugin-store @dev
```

### 前端

```bash
cd packages/siaoynli/plugin-store/resources/frontend

# 安装依赖
npm install

# 开发模式（HMR，端口 5174）
npm run dev

# 构建（输出到 public/assets/）
npm run build
```

> **注意**：修改前端代码（Vue 组件、TS、样式等）后，必须在提交前重新执行 `npm run build`，确保 `public/assets/` 下的构建产物与源码同步。

## 📦 添加插件到市场

### 方式一：Composer 安装

1. 在管理界面点击「添加插件」
2. 填写 Composer 包名（如 `vendor/package-name`）和显示名称
3. 点击「安装」→ 输入版本号（可选）→ 通过 Composer 安装

### 方式二：Zip 上传

1. 准备符合规范的 zip 包（包含 `composer.json` + `extra.plugin.class`）
2. 在管理界面点击「添加插件」
3. 填写基本信息后，选择「上传 Zip」方式安装

### Zip 包规范

```json
{
    "name": "vendor/my-plugin",
    "version": "1.0.0",
    "autoload": {
        "psr-4": { "Vendor\\MyPlugin\\": "src/" }
    },
    "extra": {
        "plugin": {
            "class": "Vendor\\MyPlugin\\MyPluginPlugin"
        }
    }
}
```

## 📝 常用命令

```bash
# 运行迁移
php artisan migrate

# 刷新插件缓存
php artisan plugin:cache
php artisan plugin:clear

# 查看已注册插件
php artisan plugin:list
```

## 🤝 贡献

欢迎提交 Issue 和 Pull Request。

## 📄 许可证

MIT License
