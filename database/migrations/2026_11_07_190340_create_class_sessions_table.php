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
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('schedule_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('capacity')->default(12);
            $table->unsignedInteger('price_cents')->default(0);
            $table->string('status')->default('scheduled'); // scheduled | cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['schedule_template_id', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
