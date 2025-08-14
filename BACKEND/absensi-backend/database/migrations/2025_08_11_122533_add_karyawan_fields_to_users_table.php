<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik')->nullable()->unique()->after('role');
            $table->string('position')->nullable()->after('nik');
            $table->string('phone')->nullable()->after('position');
            $table->text('address')->nullable()->after('phone');
            $table->string('profile_photo_path', 2048)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nik', 'position', 'phone', 'address', 'profile_photo_path']);
        });
    }
};