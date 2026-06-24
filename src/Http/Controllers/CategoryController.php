<?php

namespace Siaoynli\PluginStore\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Siaoynli\PluginStore\Http\Resources\PluginCategoryResource;
use Siaoynli\PluginStore\Services\PluginMarketplaceService;

class CategoryController extends Controller
{
    public function __construct(
        protected PluginMarketplaceService $marketplace,
    ) {}

    /**
     * 获取所有分类
     */
    public function index(): JsonResponse
    {
        $categories = $this->marketplace->categories();

        return response()->json([
            'data' => PluginCategoryResource::collection($categories),
        ]);
    }

    /**
     * 创建分类
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'slug' => ['required', 'string', 'max:50', 'unique:plugin_categories,slug', 'alpha_dash'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $category = $this->marketplace->createCategory($data);

        return response()->json([
            'data' => new PluginCategoryResource($category),
            'message' => '分类创建成功',
        ], 201);
    }

    /**
     * 更新分类
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:50'],
            'slug' => ['sometimes', 'string', 'max:50', 'unique:plugin_categories,slug,' . $id, 'alpha_dash'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $category = $this->marketplace->updateCategory($id, $data);

        return response()->json([
            'data' => new PluginCategoryResource($category),
            'message' => '分类更新成功',
        ]);
    }

    /**
     * 删除分类
     */
    public function destroy(int $id): JsonResponse
    {
        $this->marketplace->deleteCategory($id);

        return response()->json([
            'message' => '分类已删除',
        ]);
    }
}
