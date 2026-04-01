<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
                $table->integer('passengers')->default(1);
                $table->decimal('amount', 10, 2)->default(0);
                $table->enum('status', [
                    'pending', 'confirmed', 'accepted', 'rejected',
                    'cancelled', 'paid', 'completed',
                ])->default('pending');
                $table->timestamp('booked_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
