<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Identifier unik, e.g. downloader_provider, openai, gemini');
            $table->string('label')->comment('Nama tampilan, e.g. Downloader Provider, OpenAI, Gemini');
            $table->text('description')->nullable()->comment('Deskripsi kegunaan API key ini');
            $table->text('value')->nullable()->comment('API key value (encrypted)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
