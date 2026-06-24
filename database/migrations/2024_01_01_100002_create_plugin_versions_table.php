<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained('plugins')->cascadeOnDelete()->comment('关联插件');
            $table->string('version', 50)->comment('版本号');
            $table->text('changelog')->nullable()->comment('更新日志');
            $table->string('file_path', 255)->nullable()->comment('zip 包存储路径');
            $table->bigInteger('file_size')->default(0)->comment('文件大小 (bytes)');
            $table->string('min_php_version', 20)->nullable()->comment('PHP 最低版本');
            $table->string('min_laravel_version', 20)->nullable()->comment('Laravel 最低版本');
            $table->integer('download_count')->default(0)->comment('该版本下载次数');
            $table->boolean('is_latest')->default(false)->comment('是否最新版本');
            $table->timestamps();

            $table->unique(['plugin_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_versions');
    }
};
