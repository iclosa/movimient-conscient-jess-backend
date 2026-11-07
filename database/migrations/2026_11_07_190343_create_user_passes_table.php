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
        Schema::create('user_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pass_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('classes_total')->nullable();
            $table->unsignedSmallInteger('classes_remaining')->nullable(); // null = il·limitat
            $table->date('starts_on');
            $table->date('expires_on')->nullable();
            $table->string('status')->default('active'); // active | expired | exhausted | cancelled
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_passes');
    }
};
