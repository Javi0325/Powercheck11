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
        Schema::create('rutinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrenador_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('atleta_id')->nullable()->constrained('atletas')->nullOnDelete(); // Puede ser nulo si es una rutina genérica
            $table->string('nombre');
            $table->string('objetivo');
            $table->tinyInteger('dias_por_semana'); // Número de días por semana puede ser entre 1 y 6 días
            $table->tinyInteger('duracion_semanas'); // Duración en semanas
            $table->string('version'); // Versión de la rutina Si es una rutina sin asignar a un atleta será el nombre de la rutina, pero si la rutina ya fue asignada a un atleta, el nombre de la rutina mas el nombre del atelta asignado o el id para evitar repeciociones y así poder editar la rutina

            // Progreso mínimo para "pasar al siguiente día"
            $table->unsignedTinyInteger('semana_actual')->default(1);
            $table->unsignedTinyInteger('dia_actual')->default(1);
            $table->timestamp('ultimo_dia_completado_at')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rutinas');
    }
};
