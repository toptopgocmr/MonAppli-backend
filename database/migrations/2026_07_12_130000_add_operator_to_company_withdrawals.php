<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_withdrawals', function (Blueprint $table) {
            $table->string('operator')->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('company_withdrawals', function (Blueprint $table) {
            $table->dropColumn('operator');
        });
    }
};
