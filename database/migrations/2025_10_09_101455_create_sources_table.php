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
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('identifier')->unique();
            $table->string('api_name');
            $table->string('url')->nullable();
            $table->text('description')->nullable();
            $table->string('language', 10)->default('en');
            $table->string('country', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('categories')->nullable();
            $table->json('api_config')->nullable();
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'api_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
