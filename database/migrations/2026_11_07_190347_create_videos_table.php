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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->default('classe'); // classe | rutina | respiracio | meditacio
            $table->string('provider')->default('vimeo'); // vimeo | bunny | youtube | url
            $table->string('video_ref'); // ID o URL en el proveedor
            $table->string('thumbnail_path')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('level')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->unsignedInteger('price_cents')->default(0);
            $table->boolean('is_published')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
