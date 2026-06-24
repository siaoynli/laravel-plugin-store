<?php

namespace Siaoynli\PluginStore\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Siaoynli\PluginStore\Http\Requests\StorePluginRequest;
use Siaoynli\PluginStore\Http\Requests\UpdatePluginRequest;
use Siaoynli\PluginStore\Http\Resources\PluginResource;
use Siaoynli\PluginStore\Services\PluginMarketplaceService;

class PluginController extends Controller
{
    public function __construct(
        protected PluginMarketplaceService $marketplace,
    ) {}

    /**
     * 插件列表（支持搜索、分类筛选、状态筛选、分页）
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['keyword', 'category_id', 'status']);
        $perPage = $request->integer('per_page', 15);

        $plugins = $this->marketplace->list($filters, $perPage);

        return response()->json([
            'data' => PluginResource::collection($plugins->items()),
            'meta' => [
                'current_page' => $plugins->currentPage(),
                'last_page' => $plugins->lastPage(),
                'per_page' => $plugins->perPage(),
                'total' => $plugins->total(),
            ],
        ]);
    }

    /**
     * 插件详情
     */
    public function show(int $id): JsonResponse
    {
        $plugin = $this->marketplace->show($id);

        return response()->json([
            'data' => new PluginResource($plugin),
        ]);
    }

    /**
     * 创建插件记录
     */
    public function store(StorePluginRequest $request): JsonResponse
    {
        $plugin = $this->marketplace->create($request->validated());

        return response()->json([
            'data' => new PluginResource($plugin),
            'message' => '插件创建成功',
        ], 201);
    }

    /**
     * 更新插件信息
     */
    public function update(UpdatePluginRequest $request, int $id): JsonResponse
    {
        $plugin = $this->marketplace->update($id, $request->validated());

        return response()->json([
            'data' => new PluginResource($plugin),
            'message' => '插件更新成功',
        ]);
    }

    /**
     * 删除插件（软删除）
     */
    public function destroy(int $id): JsonResponse
    {
        $this->marketplace->delete($id);

        return response()->json([
            'message' => '插件已删除',
        ]);
    }

    /**
     * 切换插件状态
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $plugin = $this->marketplace->toggleStatus($id);

        return response()->json([
            'data' => new PluginResource($plugin),
            'message' => $plugin->isActive() ? '插件已启用' : '插件已禁用',
        ]);
    }
}
