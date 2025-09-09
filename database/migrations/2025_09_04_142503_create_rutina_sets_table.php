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
        Schema::create('rutina_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rutina_id')->constrained('rutinas')->cascadeOnDelete();
            $table->foreignId('ejercicio_id')->constrained('ejercicios')->cascadeOnDelete();

            $table->unsignedTinyInteger('semana');      // 1..N
            $table->unsignedTinyInteger('dia_semana');  // 1..6
            $table->unsignedSmallInteger('orden')->default(1);

            $table->unsignedTinyInteger('series')->default(1);
            $table->unsignedSmallInteger('repeticiones')->default(0); // 0 = AMRAP
            $table->decimal('peso_kg', 6, 2)->nullable();
            $table->unsignedTinyInteger('rpe')->nullable();
            $table->text('notas')->nullable();

            $table->timestamps();

            $table->index(['rutina_id', 'semana', 'dia_semana']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rutina_sets');
    }
};
