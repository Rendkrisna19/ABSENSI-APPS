        <?php

        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;

        return new class extends Migration
        {
            public function up(): void
            {
                Schema::create('locations', function (Blueprint $table) {
                    $table->id();
                    $table->string('name');
                    $table->text('address');
                    $table->double('latitude', 10, 7);
                    $table->double('longitude', 10, 7);
                    $table->integer('radius')->comment('Radius dalam meter');
                    $table->timestamps();
                });
            }

            public function down(): void
            {
                Schema::dropIfExists('locations');
            }
        };
        