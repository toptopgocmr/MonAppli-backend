<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Table ratings ─────────────────────────────────────────────────────
        if (!Schema::hasTable('ratings')) {
            Schema::create('ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
                $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->unsignedTinyInteger('driver_rating')->nullable();  // note donnée AU chauffeur (par le passager)
                $table->unsignedTinyInteger('user_rating')->nullable();    // note donnée AU passager (par le chauffeur)
                $table->text('driver_comment')->nullable();
                $table->text('user_comment')->nullable();
                $table->timestamps();
                $table->unique('booking_id'); // une seule notation par réservation
            });
        }

        // ── Colonnes préférences dans drivers ─────────────────────────────────
        Schema::table('drivers', function (Blueprint $table) {
            if (!Schema::hasColumn('drivers', 'pref_music'))
                $table->boolean('pref_music')->default(true)->after('driver_status');
            if (!Schema::hasColumn('drivers', 'pref_talk'))
                $table->boolean('pref_talk')->default(true)->after('pref_music');
            if (!Schema::hasColumn('drivers', 'pref_smoking'))
                $table->boolean('pref_smoking')->default(false)->after('pref_talk');
            if (!Schema::hasColumn('drivers', 'pref_pets'))
                $table->boolean('pref_pets')->default(false)->after('pref_smoking');
            if (!Schema::hasColumn('drivers', 'pref_max_two_back'))
                $table->boolean('pref_max_two_back')->default(false)->after('pref_pets');
            if (!Schema::hasColumn('drivers', 'rating_avg'))
                $table->decimal('rating_avg', 3, 2)->default(0)->after('pref_max_two_back');
            if (!Schema::hasColumn('drivers', 'rating_count'))
                $table->unsignedInteger('rating_count')->default(0)->after('rating_avg');
            if (!Schema::hasColumn('drivers', 'phone_verified_at'))
                $table->timestamp('phone_verified_at')->nullable()->after('rating_count');
            if (!Schema::hasColumn('drivers', 'email'))
                $table->string('email')->nullable()->after('phone');
            if (!Schema::hasColumn('drivers', 'email_verified_at'))
                $table->timestamp('email_verified_at')->nullable()->after('email');
        });

        // ── Colonnes annulation dans trips ────────────────────────────────────
        Schema::table('trips', function (Blueprint $table) {
            if (!Schema::hasColumn('trips', 'cancelled_at'))
                $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            if (!Schema::hasColumn('trips', 'cancellation_reason'))
                $table->string('cancellation_reason')->nullable()->after('cancelled_at');
        });

        // ── Colonnes embarquement + annulation dans bookings ─────────────────
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'boarded_at'))
                $table->timestamp('boarded_at')->nullable()->after('booked_at');
            if (!Schema::hasColumn('bookings', 'cancelled_at'))
                $table->timestamp('cancelled_at')->nullable()->after('boarded_at');
            if (!Schema::hasColumn('bookings', 'cancellation_reason'))
                $table->string('cancellation_reason')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn([
                'pref_music', 'pref_talk', 'pref_smoking', 'pref_pets',
                'pref_max_two_back', 'rating_avg', 'rating_count',
                'phone_verified_at', 'email', 'email_verified_at',
            ]);
        });
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['cancelled_at', 'cancellation_reason']);
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['boarded_at', 'cancelled_at', 'cancellation_reason']);
        });
    }
};
