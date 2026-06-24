<?php

namespace Siaoynli\PluginStore\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Siaoynli\PluginStore\Models\Plugin;
use Siaoynli\PluginStore\Models\PluginCategory;

/**
 * 插件市场核心业务服务
 *
 * 负责插件的 CRUD、状态管理、以及与已安装插件的同步。
 */
class PluginMarketplaceService
{
    public function __construct(
        protected ComposerInstallerService $composerInstaller,
        protected ZipUploadService $zipUploader,
    ) {}

    /**
     * 获取插件列表（支持搜索、分类、状态筛选、分页）
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Plugin::query()
            ->with(['category', 'latestVersion'])
            ->search($filters['keyword'] ?? null)
            ->ofCategory($filters['category_id'] ?? null)
            ->ofStatus($filters['status'] ?? null)
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * 获取插件详情
     */
    public function show(int $id): Plugin
    {
        return Plugin::with(['category', 'versions', 'latestVersion'])
            ->findOrFail($id);
    }

    /**
     * 创建插件记录
     */
    public function create(array $data): Plugin
    {
        return Plugin::create($data);
    }

    /**
     * 更新插件信息
     */
    public function update(int $id, array $data): Plugin
    {
        $plugin = Plugin::findOrFail($id);
        $plugin->update($data);

        return $plugin->fresh(['category', 'latestVersion']);
    }

    /**
     * 删除插件（软删除）
     */
    public function delete(int $id): bool
    {
        $plugin = Plugin::findOrFail($id);

        return $plugin->delete();
    }

    /**
     * 切换插件状态（启用/禁用）
     */
    public function toggleStatus(int $id): Plugin
    {
        $plugin = Plugin::findOrFail($id);
        $plugin->status = $plugin->isActive()
            ? Plugin::STATUS_INACTIVE
            : Plugin::STATUS_ACTIVE;
        $plugin->save();

        return $plugin;
    }

    /**
     * 通过 Composer 安装插件
     */
    public function installViaComposer(int $pluginId, ?string $version = null): array
    {
        $plugin = Plugin::findOrFail($pluginId);

        $result = $this->composerInstaller->install($plugin->package_name, $version);

        if ($result['success']) {
            $plugin->update([
                'install_type' => Plugin::INSTALL_TYPE_COMPOSER,
                'installed_version' => $result['version'],
                'installed_path' => $result['path'],
                'status' => Plugin::STATUS_ACTIVE,
            ]);
            $plugin->incrementDownloads();
        }

        return $result;
    }

    /**
     * 通过 Zip 上传安装插件
     */
    public function installViaUpload(int $pluginId, $file): array
    {
        $plugin = Plugin::findOrFail($pluginId);

        $result = $this->zipUploader->upload($file, $plugin->package_name);

        if ($result['success']) {
            $plugin->update([
                'install_type' => Plugin::INSTALL_TYPE_UPLOAD,
                'installed_version' => $result['version'],
                'installed_path' => $result['path'],
                'status' => Plugin::STATUS_ACTIVE,
            ]);
            $plugin->incrementDownloads();
        }

        return $result;
    }

    /**
     * 卸载插件
     */
    public function uninstall(int $pluginId): array
    {
        $plugin = Plugin::findOrFail($pluginId);

        if ($plugin->install_type === Plugin::INSTALL_TYPE_COMPOSER) {
            $result = $this->composerInstaller->uninstall($plugin->package_name);
        } else {
            $result = $this->zipUploader->uninstall($plugin->installed_path);
        }

        if ($result['success']) {
            $plugin->update([
                'installed_version' => null,
                'installed_path' => null,
                'status' => Plugin::STATUS_INACTIVE,
            ]);
        }

        return $result;
    }

    /**
     * 刷新已安装插件信息
     *
     * 扫描 packages/ 目录和 vendor/composer/installed.json，
     * 与数据库记录同步安装状态。
     */
    public function refreshInstalledInfo(): array
    {
        $synced = [];
        $packagesPath = config('plugins.discovery.packages_path', 'packages');
        $fullPackagesPath = base_path($packagesPath);

        // 扫描 packages/ 目录
        if (is_dir($fullPackagesPath)) {
            $dirs = glob($fullPackagesPath . '/*/*', GLOB_ONLYDIR) ?: [];

            foreach ($dirs as $dir) {
                $composerFile = $dir . '/composer.json';
                if (!file_exists($composerFile)) {
                    continue;
                }

                $composerData = json_decode(file_get_contents($composerFile), true);
                $packageName = $composerData['name'] ?? null;

                if (!$packageName) {
                    continue;
                }

                $plugin = Plugin::where('package_name', $packageName)->first();

                if ($plugin) {
                    $plugin->update([
                        'installed_version' => $composerData['version'] ?? $plugin->installed_version,
                        'installed_path' => $dir,
                        'status' => $plugin->status === Plugin::STATUS_PENDING
                            ? Plugin::STATUS_ACTIVE
                            : $plugin->status,
                    ]);

                    $synced[] = $packageName;
                }
            }
        }

        // 扫描 vendor 已安装包
        $installedJsonPath = base_path('vendor/composer/installed.json');
        if (file_exists($installedJsonPath)) {
            $installed = json_decode(file_get_contents($installedJsonPath), true);
            $packages = $installed['packages'] ?? $installed;

            foreach ($packages as $package) {
                $packageName = $package['name'] ?? null;

                if (!$packageName || in_array($packageName, $synced)) {
                    continue;
                }

                $plugin = Plugin::where('package_name', $packageName)->first();
                if ($plugin && !empty($package['extra']['plugin']['class'])) {
                    $vendorPath = base_path('vendor/' . $packageName);
                    $plugin->update([
                        'installed_version' => $package['version'] ?? $plugin->installed_version,
                        'installed_path' => $vendorPath,
                        'install_type' => Plugin::INSTALL_TYPE_COMPOSER,
                        'status' => $plugin->status === Plugin::STATUS_PENDING
                            ? Plugin::STATUS_ACTIVE
                            : $plugin->status,
                    ]);

                    $synced[] = $packageName;
                }
            }
        }

        return [
            'synced' => $synced,
            'count' => count($synced),
        ];
    }

    /**
     * 获取所有分类
     */
    public function categories(): Collection
    {
        return PluginCategory::ordered()->get();
    }

    /**
     * 创建分类
     */
    public function createCategory(array $data): PluginCategory
    {
        return PluginCategory::create($data);
    }

    /**
     * 更新分类
     */
    public function updateCategory(int $id, array $data): PluginCategory
    {
        $category = PluginCategory::findOrFail($id);
        $category->update($data);

        return $category;
    }

    /**
     * 删除分类
     */
    public function deleteCategory(int $id): bool
    {
        $category = PluginCategory::findOrFail($id);

        // 将分类下的插件设为无分类
        Plugin::where('category_id', $id)->update(['category_id' => null]);

        return $category->delete();
    }
}
