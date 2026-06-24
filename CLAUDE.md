# CLAUDE.md — siaoynli/plugin-store

## 项目概览

这是一个 **Laravel 11 插件市场/仓库管理包**，基于 `siaoynli/laravel-plugins` 插件系统构建。提供插件的增删改查、安装（Composer / Zip 上传）、卸载等完整管理能力。前端为内嵌 Vue3 SPA。

## 技术栈

- **后端**: Laravel 11.x / PHP ^8.2
- **插件系统**: siaoynli/laravel-plugins (AbstractPlugin + PluginInterface)
- **前端**: Vue 3 + TypeScript + Naive UI
- **构建**: Vite 5
- **数据库**: MySQL

## 包结构

```
laravel-plugin-store/
├── composer.json                       # 包声明 (siaoynli/plugin-store)
├── config/plugin.php                   # 插件配置
├── src/
│   ├── PluginStorePlugin.php           # 插件主类 (extends AbstractPlugin)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── PluginController.php        # 插件 CRUD API
│   │   │   ├── PluginInstallController.php # 安装/卸载/上传/同步
│   │   │   └── CategoryController.php      # 分类 CRUD API
│   │   ├── Requests/                       # Form Request 验证
│   │   └── Resources/                      # API Resource 格式化
│   ├── Models/
│   │   ├── Plugin.php                      # 插件模型 (软删除)
│   │   ├── PluginVersion.php               # 版本模型
│   │   └── PluginCategory.php              # 分类模型
│   └── Services/
│       ├── PluginMarketplaceService.php    # 核心业务逻辑
│       ├── ComposerInstallerService.php    # composer require/remove
│       └── ZipUploadService.php            # zip 上传解压安装
├── database/migrations/                    # 3 个迁移文件
├── routes/
│   ├── api.php                             # API 路由 (/api/plugin-store/*)
│   └── web.php                             # SPA catch-all (/plugin-store/*)
├── resources/
│   ├── views/app.blade.php                 # SPA 入口 Blade
│   └── frontend/                           # Vue 3 前端
│       ├── package.json
│       ├── vite.config.ts
│       ├── tsconfig.json
│       └── src/
│           ├── main.ts                     # Vue 入口
│           ├── App.vue                     # 根组件 (侧栏布局)
│           ├── router/index.ts             # Vue Router
│           ├── api/plugin.ts               # API 封装 + TS 类型
│           ├── pages/                      # 页面组件
│           │   ├── PluginList.vue          # 插件列表 (搜索/筛选/分页/安装)
│           │   ├── PluginCreate.vue        # 添加插件
│           │   ├── PluginEdit.vue          # 编辑 (基本信息/安装/版本)
│           │   └── CategoryManage.vue      # 分类管理
│           └── components/
│               ├── PluginCard.vue          # 插件卡片
│               └── VersionTag.vue          # 版本标签
└── resources/
    └── assets/                             # Vite build 产物（由框架 PluginPublisher 自动发布）
```

## 数据库表

### plugin_categories
| 字段 | 说明 |
|------|------|
| id | 主键 |
| name | 分类名 (50) |
| slug | URL slug (50, unique) |
| description | 描述 |
| sort_order | 排序权重 |

### plugins
| 字段 | 说明 |
|------|------|
| id | 主键 |
| category_id | 分类 FK |
| package_name | Composer 包名 (unique) |
| display_name | 显示名 |
| slug | URL slug (unique) |
| description | 描述 |
| author | 作者 |
| homepage | 主页链接 |
| icon | 图标路径 |
| status | active / inactive / pending |
| install_type | composer / upload |
| installed_version | 当前安装版本 |
| installed_path | 安装路径 |
| download_count | 下载次数 |
| settings | JSON 配置 |

### plugin_versions
| 字段 | 说明 |
|------|------|
| id | 主键 |
| plugin_id | 插件 FK |
| version | 版本号 |
| changelog | 更新日志 |
| file_path | zip 路径 |
| file_size | 文件大小 |
| min_php_version | PHP 最低版本 |
| min_laravel_version | Laravel 最低版本 |
| is_latest | 是否最新 |

## API 接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/plugin-store/plugins | 插件列表 (搜索/筛选/分页) |
| GET | /api/plugin-store/plugins/{id} | 插件详情 |
| POST | /api/plugin-store/plugins | 创建插件 |
| PUT | /api/plugin-store/plugins/{id} | 更新插件 |
| DELETE | /api/plugin-store/plugins/{id} | 删除插件 (软删除) |
| PATCH | /api/plugin-store/plugins/{id}/toggle | 启用/禁用切换 |
| POST | /api/plugin-store/install | Composer 安装 |
| POST | /api/plugin-store/upload | Zip 上传安装 |
| POST | /api/plugin-store/uninstall/{id} | 卸载插件 |
| POST | /api/plugin-store/refresh | 同步已安装插件 |
| GET | /api/plugin-store/categories | 分类列表 |
| POST | /api/plugin-store/categories | 创建分类 |
| PUT | /api/plugin-store/categories/{id} | 更新分类 |
| DELETE | /api/plugin-store/categories/{id} | 删除分类 |

## 常用命令

### 后端

```bash
# 运行迁移
php artisan migrate

# 链接插件包到 Laravel 项目 (开发时)
cd /path/to/laravel11
ln -s /path/to/laravel-plugin-store packages/siaoynli/plugin-store
# 或 composer.json repositories 中添加 path 仓库

# 刷新插件缓存
php artisan plugin:cache
php artisan plugin:clear
```

### 前端

```bash
cd resources/frontend

# 安装依赖
npm install

# 开发模式 (HMR, 端口 5174)
npm run dev

# 构建 (输出到 resources/assets/)
npm run build
```

## 开发规范

### 后端
- 控制器方法瘦薄，业务逻辑放 Service 层
- 使用 FormRequest 验证输入
- 使用 API Resource 格式化输出
- 遵循 RESTful 风格
- 数据库字段 snake_case

### 前端
- Vue 3 Composition API (`<script setup>`)
- TypeScript 严格模式
- Naive UI 组件库
- API 请求统一封装在 `api/plugin.ts`
- 页面组件放 `pages/`，可复用组件放 `components/`
- 使用 scoped style 或组件库样式
- **修改了前端代码（Vue 组件、TS、样式等）后，必须在提交前重新构建**：`cd resources/frontend && npm run build`，确保 `resources/assets/` 下的构建产物与源码同步

## 部署

### 开发环境

1. 将本包放入 Laravel 项目的 `packages/siaoynli/plugin-store/`
2. 在 `composer.json` 添加 path 仓库：
```json
{
    "repositories": [
        { "type": "path", "url": "packages/siaoynli/plugin-store" }
    ]
}
```
3. `composer require siaoynli/plugin-store`
4. `php artisan migrate`
5. 访问 `/plugin-store/` 进入管理界面

### 生产环境

1. `cd resources/frontend && npm run build`
2. 发布静态资源：`php artisan vendor:publish --tag=siaoynli-plugin-store-assets`
3. `php artisan plugin:cache`
