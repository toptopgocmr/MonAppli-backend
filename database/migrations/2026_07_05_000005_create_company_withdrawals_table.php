<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('companies', 'bank_name')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('bank_name')->nullable()->after('commission_rate');
                $table->string('bank_iban')->nullable()->after('bank_name');
                $table->string('bank_swift')->nullable()->after('bank_iban');
                $table->string('bank_address')->nullable()->after('bank_swift');
            });
        }

        if (!Schema::hasTable('company_withdrawals')) {
            Schema::create('company_withdrawals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                // Renseigné au moment du traitement admin : mobile_money, bank ou manual.
                $table->string('method')->nullable();
                $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
                $table->string('transaction_ref')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_withdrawals');

        if (Schema::hasColumn('companies', 'bank_name')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn(['bank_name', 'bank_iban', 'bank_swift', 'bank_address']);
            });
        }
    }
};
