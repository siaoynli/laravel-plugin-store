<?php

namespace Siaoynli\PluginStore\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Composer 安装服务
 *
 * 通过 exec/shell 调用 composer 命令来安装/卸载 Composer 包。
 */
class ComposerInstallerService
{
    protected string $binary;
    protected int $timeout;
    protected string $workingDir;

    public function __construct()
    {
        $this->binary = config('plugins.siaoynli-plugin-store.composer.binary', 'composer');
        $this->timeout = config('plugins.siaoynli-plugin-store.composer.timeout', 300);
        $this->workingDir = config('plugins.siaoynli-plugin-store.composer.working_dir') ?? base_path();
    }

    /**
     * 安装 Composer 包
     */
    public function install(string $packageName, ?string $version = null): array
    {
        $package = $version ? "{$packageName}:{$version}" : $packageName;

        Log::info("Composer installing: {$package}");

        try {
            $result = Process::timeout($this->timeout)
                ->path($this->workingDir)
                ->run("{$this->binary} require {$package} --no-interaction --no-progress");

            if ($result->failed()) {
                $output = $result->errorOutput() ?: $result->output();
                Log::error("Composer install failed: {$output}");

                return [
                    'success' => false,
                    'message' => 'Composer 安装失败: ' . $output,
                    'output' => $output,
                ];
            }

            // 读取安装后的版本信息
            $installedVersion = $this->getInstalledVersion($packageName);
            $installedPath = $this->getInstalledPath($packageName);

            return [
                'success' => true,
                'message' => "成功安装 {$package}",
                'version' => $installedVersion,
                'path' => $installedPath,
                'output' => $result->output(),
            ];
        } catch (\Exception $e) {
            Log::error("Composer install exception: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Composer 安装异常: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 卸载 Composer 包
     */
    public function uninstall(string $packageName): array
    {
        Log::info("Composer uninstalling: {$packageName}");

        try {
            $result = Process::timeout($this->timeout)
                ->path($this->workingDir)
                ->run("{$this->binary} remove {$packageName} --no-interaction --no-progress");

            if ($result->failed()) {
                $output = $result->errorOutput() ?: $result->output();
                Log::error("Composer uninstall failed: {$output}");

                return [
                    'success' => false,
                    'message' => 'Composer 卸载失败: ' . $output,
                    'output' => $output,
                ];
            }

            return [
                'success' => true,
                'message' => "成功卸载 {$packageName}",
                'output' => $result->output(),
            ];
        } catch (\Exception $e) {
            Log::error("Composer uninstall exception: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Composer 卸载异常: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 获取已安装版本
     */
    public function getInstalledVersion(string $packageName): ?string
    {
        try {
            $result = Process::timeout(30)
                ->path($this->workingDir)
                ->run("{$this->binary} show {$packageName} --format=json --no-interaction");

            if ($result->successful()) {
                $data = json_decode($result->output(), true);
                return $data['versions'][0] ?? $data['version'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning("Failed to get version for {$packageName}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * 获取安装路径
     */
    public function getInstalledPath(string $packageName): ?string
    {
        $path = base_path('vendor/' . $packageName);
        return is_dir($path) ? $path : null;
    }

    /**
     * 获取包的可安装版本列表
     */
    public function getAvailableVersions(string $packageName): array
    {
        try {
            $result = Process::timeout(60)
                ->path($this->workingDir)
                ->run("{$this->binary} show {$packageName} --all --format=json --no-interaction");

            if ($result->successful()) {
                $data = json_decode($result->output(), true);
                return $data['versions'] ?? [];
            }
        } catch (\Exception $e) {
            Log::warning("Failed to get versions for {$packageName}: " . $e->getMessage());
        }

        return [];
    }
}
