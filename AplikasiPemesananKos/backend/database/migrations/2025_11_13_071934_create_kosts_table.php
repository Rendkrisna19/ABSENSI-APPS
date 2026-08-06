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
        Schema::create('kosts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city')->nullable();          // Kuningan, Jawa Barat
            $table->string('address');                  // alamat lengkap
            $table->integer('price_per_month');         // Rp 1.500.000
            $table->float('rating')->default(0);        // 4.7
            $table->integer('review_count')->default(0); // 100 reviewers
            $table->integer('available_rooms')->default(0); // 3 tersedia
            $table->json('facilities')->nullable();     // ["TV","Lemari","Tempat tidur","AC"]
            $table->text('property_rules')->nullable(); // kebijakan properti
            $table->text('location_detail')->nullable(); // detail lokasi
            $table->string('map_embed')->nullable();    // iframe / link map (opsional)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kosts');
    }
};
