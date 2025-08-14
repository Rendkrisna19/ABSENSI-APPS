    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
        {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->dateTime('check_in_time')->nullable();
                $table->double('check_in_latitude', 10, 7)->nullable();
                $table->double('check_in_longitude', 10, 7)->nullable();
                $table->dateTime('check_out_time')->nullable();
                $table->double('check_out_latitude', 10, 7)->nullable();
                $table->double('check_out_longitude', 10, 7)->nullable();
                $table->string('status'); // Contoh: Hadir, Terlambat, Izin, Sakit, Absen
                $table->text('reason')->nullable(); // Alasan jika izin/sakit
                $table->timestamps();
            });
        }

        public function down(): void
        {
            Schema::dropIfExists('attendances');
        }
    };
    