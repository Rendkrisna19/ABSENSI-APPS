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
        // PENTING: Gunakan Schema::table, BUKAN Schema::create
        Schema::table('transactions', function (Blueprint $table) {
            
            // Cek dulu biar tidak error kalau dijalankan ulang (opsional tapi aman)
            if (!Schema::hasColumn('transactions', 'tenant_name')) {
                // --- FITUR 1: BOOKING UNTUK ORANG LAIN ---
                $table->string('tenant_name')->nullable()->after('status'); 
                $table->string('tenant_phone')->nullable()->after('tenant_name');
                $table->string('tenant_type')->default('self')->after('tenant_phone');
            }

            if (!Schema::hasColumn('transactions', 'rent_status')) {
                // --- FITUR 2: SIKLUS HIDUP SEWA & REFUND ---
                $table->string('rent_status')->default('UPCOMING')->after('tenant_type'); 
                $table->date('stopped_at')->nullable()->after('rent_status'); 
                $table->bigInteger('refund_amount')->default(0)->after('stopped_at'); 
                $table->bigInteger('penalty_amount')->default(0)->after('refund_amount'); 
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'tenant_name',
                'tenant_phone',
                'tenant_type',
                'rent_status',
                'stopped_at',
                'refund_amount',
                'penalty_amount'
            ]);
        });
    }
};