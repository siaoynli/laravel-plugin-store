<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('分类名称');
            $table->string('slug', 50)->unique()->comment('URL Slug');
            $table->text('description')->nullable()->comment('分类描述');
            $table->integer('sort_order')->default(0)->comment('排序权重');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_categories');
    }
};
