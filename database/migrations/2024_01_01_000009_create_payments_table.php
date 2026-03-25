<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('driver_net', 10, 2)->default(0);
            $table->enum('method', ['mtn', 'orange', 'airtel', 'moov', 'visa', 'mastercard']);
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->string('transaction_ref')->unique()->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
