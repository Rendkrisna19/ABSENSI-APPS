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
    Schema::table('transactions', function (Blueprint $table) {
        // Info Rekening User untuk Refund
        $table->string('refund_bank_name')->nullable()->after('penalty_amount'); // Misal: BCA, BRI
        $table->string('refund_account_number')->nullable()->after('refund_bank_name');
        $table->string('refund_account_name')->nullable()->after('refund_account_number');
        
        // Bukti transfer dari admin (Opsional)
        $table->string('refund_proof')->nullable()->after('refund_account_name');
    });
}

public function down(): void
{
    Schema::table('transactions', function (Blueprint $table) {
        $table->dropColumn(['refund_bank_name', 'refund_account_number', 'refund_account_name', 'refund_proof']);
    });
}
};
