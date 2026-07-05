<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_withdrawals') && !Schema::hasColumn('company_withdrawals', 'country')) {
            Schema::table('company_withdrawals', function (Blueprint $table) {
                $table->string('country', 5)->nullable()->after('method');
                $table->string('phone_number')->nullable()->after('country');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('company_withdrawals') && Schema::hasColumn('company_withdrawals', 'country')) {
            Schema::table('company_withdrawals', function (Blueprint $table) {
                $table->dropColumn(['country', 'phone_number']);
            });
        }
    }
};
