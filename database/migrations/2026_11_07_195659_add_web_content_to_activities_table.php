<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contingut públic de cada activitat (web) i preu de la classe solta.
     * El català és l'idioma base; els camps *_es són la traducció al castellà.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedInteger('price_cents')->nullable()->after('level');

            $table->string('h1')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->text('for_who')->nullable();
            $table->text('expect')->nullable();
            $table->text('benefits')->nullable();
            $table->text('faq')->nullable();

            $table->string('name_es')->nullable();
            $table->string('slug_es')->nullable()->unique();
            $table->string('tagline_es')->nullable();
            $table->text('description_es')->nullable();
            $table->string('level_es')->nullable();
            $table->string('h1_es')->nullable();
            $table->string('meta_title_es')->nullable();
            $table->string('meta_description_es', 320)->nullable();
            $table->text('for_who_es')->nullable();
            $table->text('expect_es')->nullable();
            $table->text('benefits_es')->nullable();
            $table->text('faq_es')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropUnique(['slug_es']);
            $table->dropColumn([
                'price_cents', 'h1', 'meta_title', 'meta_description', 'for_who', 'expect', 'benefits', 'faq',
                'name_es', 'slug_es', 'tagline_es', 'description_es', 'level_es', 'h1_es', 'meta_title_es',
                'meta_description_es', 'for_who_es', 'expect_es', 'benefits_es', 'faq_es',
            ]);
        });
    }
};
