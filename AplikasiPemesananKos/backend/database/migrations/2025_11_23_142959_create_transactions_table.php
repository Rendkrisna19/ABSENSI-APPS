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
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        // Relasi ke User (Penyewa)
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        // Relasi ke Kost
        $table->foreignId('kost_id')->constrained()->onDelete('cascade');
        
        // Data Booking
        $table->date('start_date'); // Tanggal mulai ngekos
        $table->integer('duration'); // Durasi (bulan)
        $table->bigInteger('total_price'); // Total bayar
        
        // Data Xendit / Pembayaran
        $table->string('external_id')->unique(); // ID unik buat Xendit
        $table->string('payment_url')->nullable(); // Link bayar Xendit
        $table->string('status')->default('PENDING'); // PENDING, PAID, EXPIRED, FAILED
        
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
