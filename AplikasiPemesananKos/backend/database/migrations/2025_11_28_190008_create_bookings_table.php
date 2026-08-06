<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // Pemesan (Akun yang bayar)
            $table->foreignId('kost_id')->constrained('kosts');
            
            // Fitur Pesan Buat Orang Lain
            $table->string('tenant_name'); // Nama penghuni (bisa diri sendiri atau orang lain)
            $table->string('tenant_phone');
            $table->string('tenant_type')->default('self'); // self, family, friend, partner
            
            // Info Durasi
            $table->date('start_date');
            $table->integer('duration_months'); // Misal: 12 bulan
            $table->date('end_date');
            
            // Info Harga
            $table->decimal('total_price', 15, 2); // Harga awal (20 juta)
            
            // Fitur Berhenti & Refund
            $table->date('stopped_at')->nullable(); // Tanggal berhenti
            $table->decimal('refund_amount', 15, 2)->default(0); // Uang kembali
            $table->decimal('penalty_amount', 15, 2)->default(0); // Denda 10%
            
            $table->enum('status', ['active', 'completed', 'stopped', 'cancelled'])->default('active');
            
            $table->timestamps();
        });

        // Tabel Chat Sederhana
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users');
            $table->foreignId('receiver_id')->constrained('users'); // Bisa admin atau user lain
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chats');
        Schema::dropIfExists('bookings');
    }
};