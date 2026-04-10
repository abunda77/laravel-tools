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
        Schema::create('chat_citations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('url');
            $table->text('snippet')->nullable();
            $table->string('source_provider', 50)->default('perplexity');
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamps();

            $table->index(['chat_message_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_citations');
    }
};
