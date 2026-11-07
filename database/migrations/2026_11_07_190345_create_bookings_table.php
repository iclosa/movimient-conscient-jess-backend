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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->nullable()->constrained('children')->nullOnDelete();
            $table->foreignId('user_pass_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('booked'); // booked | waitlist | cancelled | attended | no_show
            $table->string('payment_mode')->default('pass'); // pass | free | cash | pending
            $table->string('source')->default('app'); // app | web | admin
            $table->dateTime('cancelled_at')->nullable();
            $table->boolean('late_cancellation')->default(false);
            $table->dateTime('attended_at')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('rating_comment')->nullable();
            $table->dateTime('rated_at')->nullable();
            $table->timestamps();

            $table->index(['class_session_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
