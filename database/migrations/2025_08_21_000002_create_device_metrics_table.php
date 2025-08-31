<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('device_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->unsignedBigInteger('athlete_id');
            $table->float('bpm')->nullable();
            $table->integer('repeticiones')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'athlete_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('device_metrics');
    }
};