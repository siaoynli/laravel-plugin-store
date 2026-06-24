<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('plugin_categories')->nullOnDelete()->comment('所属分类');
            $table->string('package_name', 255)->unique()->comment('Composer 包名 (vendor/package)');
            $table->string('display_name', 100)->comment('显示名称');
            $table->string('slug', 100)->unique()->comment('URL Slug');
            $table->text('description')->nullable()->comment('简介');
            $table->string('author', 100)->nullable()->comment('作者');
            $table->string('homepage', 255)->nullable()->comment('主页链接');
            $table->string('icon', 255)->nullable()->comment('图标路径');
            $table->string('status', 20)->default('pending')->comment('状态: active, inactive, pending');
            $table->string('install_type', 20)->default('composer')->comment('安装方式: composer, upload');
            $table->string('installed_version', 50)->nullable()->comment('当前安装版本');
            $table->string('installed_path', 255)->nullable()->comment('安装路径');
            $table->integer('download_count')->default(0)->comment('下载/安装次数');
            $table->json('settings')->nullable()->comment('额外配置');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
