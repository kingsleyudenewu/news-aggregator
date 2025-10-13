<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\table;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('content')->nullable();
            $table->string('author')->nullable()->index();
            $table->string('source_name')->index();
            $table->string('source_id')->index();
            $table->string('category')->nullable()->index();
            $table->string('url')->unique();
            $table->text('image_url')->nullable();
            $table->timestamp('published_at')->index();
            $table->json('metadata')->nullable();
            $table->string('content_hash')->unique();
            $table->integer('view_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index(['source_name', 'published_at']);
            $table->index(['category', 'published_at']);
            $table->fullText(['title', 'description', 'content']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
