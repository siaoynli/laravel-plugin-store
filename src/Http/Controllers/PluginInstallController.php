<?php

namespace Siaoynli\PluginStore\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Siaoynli\PluginStore\Http\Requests\InstallPluginRequest;
use Siaoynli\PluginStore\Http\Resources\PluginResource;
use Siaoynli\PluginStore\Services\PluginMarketplaceService;

class PluginInstallController extends Controller
{
    public function __construct(
        protected PluginMarketplaceService $marketplace,
    ) {}

    /**
     * 通过 Composer 安装插件
     */
    public function install(InstallPluginRequest $request): JsonResponse
    {
        $result = $this->marketplace->installViaComposer(
            $request->plugin_id,
            $request->version,
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
                'output' => $result['output'] ?? null,
            ], 422);
        }

        $plugin = $this->marketplace->show($request->plugin_id);

        return response()->json([
            'data' => new PluginResource($plugin),
            'message' => $result['message'],
        ]);
    }

    /**
     * 上传 Zip 包安装插件
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'plugin_id' => ['required', 'integer', 'exists:plugins,id'],
            'file' => ['required', 'file', 'mimes:zip', 'max:51200'],
        ]);

        $result = $this->marketplace->installViaUpload(
            $request->plugin_id,
            $request->file('file'),
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        $plugin = $this->marketplace->show($request->plugin_id);

        return response()->json([
            'data' => new PluginResource($plugin),
            'message' => $result['message'],
        ]);
    }

    /**
     * 卸载插件
     */
    public function uninstall(int $id): JsonResponse
    {
        $result = $this->marketplace->uninstall($id);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        $plugin = $this->marketplace->show($id);

        return response()->json([
            'data' => new PluginResource($plugin),
            'message' => $result['message'],
        ]);
    }

    /**
     * 刷新已安装插件信息
     */
    public function refresh(): JsonResponse
    {
        $result = $this->marketplace->refreshInstalledInfo();

        return response()->json([
            'message' => "已同步 {$result['count']} 个插件",
            'synced' => $result['synced'],
        ]);
    }
}
