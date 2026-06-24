<?php

namespace Siaoynli\PluginStore\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Zip 包上传安装服务
 *
 * 处理 zip 包上传、解压、验证、安装到 packages/ 目录。
 */
class ZipUploadService
{
    protected string $tempPath;
    protected string $packagesPath;
    protected int $maxSize;

    public function __construct()
    {
        $this->tempPath = config(
            'plugins.siaoynli-plugin-store.upload.temp_path',
            storage_path('app/plugin-store/uploads')
        );
        $this->packagesPath = config(
            'plugins.siaoynli-plugin-store.upload.packages_path',
            'packages'
        );
        $this->maxSize = config(
            'plugins.siaoynli-plugin-store.upload.max_size',
            50
        ) * 1024 * 1024;
    }

    /**
     * 上传并安装 Zip 包
     */
    public function upload(UploadedFile $file, ?string $expectedPackage = null): array
    {
        // 验证文件
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message'],
            ];
        }

        // 确保临时目录存在
        File::ensureDirectoryExists($this->tempPath);

        // 保存上传文件
        $tempFile = $file->storeAs($this->tempPath, Str::uuid() . '.zip');
        $fullTempPath = $this->tempPath . '/' . basename($tempFile);

        try {
            // 解压到临时目录
            $extractPath = $this->tempPath . '/' . Str::uuid();
            File::ensureDirectoryExists($extractPath);

            $zip = new ZipArchive();
            if ($zip->open($fullTempPath) !== true) {
                return [
                    'success' => false,
                    'message' => '无法解压 zip 文件',
                ];
            }

            $zip->extractTo($extractPath);
            $zip->close();

            // 查找 composer.json（可能在根目录或第一级子目录）
            $composerPath = $this->findComposerJson($extractPath);

            if (!$composerPath) {
                return [
                    'success' => false,
                    'message' => 'zip 包中未找到 composer.json',
                ];
            }

            $packageDir = dirname($composerPath);
            $composerData = json_decode(File::get($composerPath), true);

            // 验证包名
            $packageName = $composerData['name'] ?? null;
            if (!$packageName) {
                return [
                    'success' => false,
                    'message' => 'composer.json 中缺少 name 字段',
                ];
            }

            if ($expectedPackage && $packageName !== $expectedPackage) {
                return [
                    'success' => false,
                    'message' => "包名不匹配：期望 {$expectedPackage}，实际 {$packageName}",
                ];
            }

            // 验证插件声明
            $validation = $this->validatePackage($composerData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message'],
                ];
            }

            // 移动到 packages/ 目录
            $targetDir = $this->getTargetPath($packageName);
            File::ensureDirectoryExists(dirname($targetDir));

            // 如果目标已存在，先备份再替换
            if (is_dir($targetDir)) {
                File::deleteDirectory($targetDir);
            }

            File::moveDirectory($packageDir, $targetDir);

            $version = $composerData['version'] ?? '1.0.0';

            return [
                'success' => true,
                'message' => "成功安装 {$packageName} v{$version}",
                'package_name' => $packageName,
                'version' => $version,
                'path' => $targetDir,
                'file_size' => $file->getSize(),
            ];
        } catch (\Exception $e) {
            Log::error("Zip upload failed: " . $e->getMessage());

            return [
                'success' => false,
                'message' => '安装失败: ' . $e->getMessage(),
            ];
        } finally {
            // 清理临时文件
            File::delete($fullTempPath);
            if (isset($extractPath) && is_dir($extractPath)) {
                File::deleteDirectory($extractPath);
            }
        }
    }

    /**
     * 卸载（删除插件目录）
     */
    public function uninstall(?string $path): array
    {
        if (!$path || !is_dir($path)) {
            return [
                'success' => false,
                'message' => '插件目录不存在',
            ];
        }

        try {
            File::deleteDirectory($path);

            return [
                'success' => true,
                'message' => '成功卸载插件',
            ];
        } catch (\Exception $e) {
            Log::error("Zip uninstall failed: " . $e->getMessage());

            return [
                'success' => false,
                'message' => '卸载失败: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 验证上传文件
     */
    protected function validateFile(UploadedFile $file): array
    {
        // 检查文件大小
        if ($file->getSize() > $this->maxSize) {
            return [
                'valid' => false,
                'message' => '文件大小超过限制 (最大 ' . ($this->maxSize / 1024 / 1024) . 'MB)',
            ];
        }

        // 检查 MIME 类型
        $allowedMimes = config(
            'plugins.siaoynli-plugin-store.upload.allowed_mimes',
            ['application/zip', 'application/x-zip-compressed']
        );

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return [
                'valid' => false,
                'message' => '不支持的文件类型，仅允许 zip 文件',
            ];
        }

        return ['valid' => true];
    }

    /**
     * 验证包是否符合插件规范
     */
    public function validatePackage(array $composerData): array
    {
        // 检查 extra.plugin.class 声明
        $pluginClass = $composerData['extra']['plugin']['class'] ?? null;

        if (!$pluginClass) {
            return [
                'valid' => false,
                'message' => 'composer.json 中缺少 extra.plugin.class 声明',
            ];
        }

        return ['valid' => true];
    }

    /**
     * 在解压目录中查找 composer.json
     */
    protected function findComposerJson(string $extractPath): ?string
    {
        // 根目录
        $root = $extractPath . '/composer.json';
        if (file_exists($root)) {
            return $root;
        }

        // 第一级子目录
        $dirs = glob($extractPath . '/*', GLOB_ONLYDIR);
        foreach ($dirs ?: [] as $dir) {
            $file = $dir . '/composer.json';
            if (file_exists($file)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * 获取目标安装路径
     */
    protected function getTargetPath(string $packageName): string
    {
        return base_path($this->packagesPath . '/' . $packageName);
    }
}
