<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Plugin Store Web Routes (SPA catch-all)
|--------------------------------------------------------------------------
|
| Vue3 SPA 的 catch-all 路由，由 Vue Router 接管页面导航。
| 前缀: /plugin-store
|
*/

Route::get('/plugin-store/{any?}', function () {
    return view('plugin-store::app');
})->where('any', '.*')->name('plugin-store.app');
