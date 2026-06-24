<?php

use Illuminate\Support\Facades\Route;
use Siaoynli\PluginStore\Http\Controllers\CategoryController;
use Siaoynli\PluginStore\Http\Controllers\PluginController;
use Siaoynli\PluginStore\Http\Controllers\PluginInstallController;

/*
|--------------------------------------------------------------------------
| Plugin Store API Routes
|--------------------------------------------------------------------------
|
| 插件市场的 RESTful API 路由。
| 前缀: /api/plugin-store
|
*/

Route::prefix('api/plugin-store')->middleware('api')->group(function () {

    // 插件 CRUD
    Route::get('/plugins', [PluginController::class, 'index']);
    Route::get('/plugins/{id}', [PluginController::class, 'show']);
    Route::post('/plugins', [PluginController::class, 'store']);
    Route::put('/plugins/{id}', [PluginController::class, 'update']);
    Route::delete('/plugins/{id}', [PluginController::class, 'destroy']);
    Route::patch('/plugins/{id}/toggle', [PluginController::class, 'toggleStatus']);

    // 安装 / 卸载
    Route::post('/install', [PluginInstallController::class, 'install']);
    Route::post('/upload', [PluginInstallController::class, 'upload']);
    Route::post('/uninstall/{id}', [PluginInstallController::class, 'uninstall']);
    Route::post('/refresh', [PluginInstallController::class, 'refresh']);

    // 分类管理
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
});
