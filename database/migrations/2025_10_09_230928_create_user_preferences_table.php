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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('user_identifier')->unique();
            $table->json('preferred_sources')->nullable();
            $table->json('preferred_categories')->nullable();
            $table->json('preferred_authors')->nullable();
            $table->json('excluded_keywords')->nullable();
            $table->integer('articles_per_page')->default(20);
            $table->string('default_sort', 50)->default('published_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_prefrences');
    }
};
