<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('bookmark_categories')->nullOnDelete();
            $table->string('url', 2048);
            $table->string('title', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('favicon_url', 2048)->nullable();
            $table->string('domain', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('visited_count')->default(0);
            $table->timestamp('last_visited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('category_id');
            $table->index('domain');
            $table->index(['user_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
