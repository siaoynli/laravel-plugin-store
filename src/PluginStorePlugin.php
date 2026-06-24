<?php

namespace Siaoynli\PluginStore;

use Siaoynli\Plugins\AbstractPlugin;
use Illuminate\Support\Facades\File;

/**
 * Plugin Store — 插件市场/仓库管理插件
 *
 * 提供插件的增删改查、安装（Composer / Zip 上传）、卸载等完整管理能力。
 * 前端为内嵌 Vue3 SPA，后端提供 RESTful API。
 */
class PluginStorePlugin extends AbstractPlugin
{
    protected bool $enabled = true;

    /**
     * 启动阶段 — 注册视图命名空间
     */
    public function boot(): void
    {
        // 注册 Blade 视图命名空间
        $viewsPath = $this->getBasePath() . '/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'plugin-store');
        }
    }

    /**
     * 注册阶段 — 合并配置 + 加载迁移 + 注册 ServiceProviders
     */
    public function register(): void
    {
        parent::register();

        // 加载迁移文件
        $this->loadMigrations();
    }

    /**
     * 注册路由
     */
    public function registerRoutes(): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        $routesDir = $this->getBasePath() . '/routes';

        if (!is_dir($routesDir)) {
            return;
        }

        // 加载 API 路由
        $apiRouteFile = $routesDir . '/api.php';
        if (File::exists($apiRouteFile)) {
            require $apiRouteFile;
        }

        // 加载 Web 路由（SPA catch-all）
        $webRouteFile = $routesDir . '/web.php';
        if (File::exists($webRouteFile)) {
            require $webRouteFile;
        }
    }

    /**
     * 加载迁移文件
     */
    protected function loadMigrations(): void
    {
        $migrationsPath = $this->getBasePath() . '/database/migrations';

        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    /**
     * 从 ServiceProvider 加载迁移
     */
    protected function loadMigrationsFrom(string $path): void
    {
        $this->app->afterResolving('migrator', function ($migrator) use ($path) {
            $migrator->path($path);
        });
    }

    /**
     * 从 ServiceProvider 加载视图
     */
    protected function loadViewsFrom(string $path, string $namespace): void
    {
        $this->app->afterResolving('view', function ($view) use ($path, $namespace) {
            $view->addNamespace($namespace, $path);
        });
    }

    /**
     * 声明可发布资源
     */
    public function publish(): array
    {
        return [
            // 前端 dist 到 public
            $this->getBasePath() . '/public' => public_path('plugins/plugin-store'),
            // 配置文件
            $this->getBasePath() . '/config/plugin.php' => config_path('plugins/siaoynli-plugin-store.php'),
            // 迁移文件
            $this->getBasePath() . '/database/migrations' => database_path('migrations'),
        ];
    }
}
