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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->text('intro')->nullable();
            $table->longText('body')->nullable();
            $table->string('title_es')->nullable();
            $table->string('slug_es')->nullable()->unique();
            $table->text('excerpt_es')->nullable();
            $table->text('intro_es')->nullable();
            $table->longText('body_es')->nullable();
            $table->string('image_path')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
