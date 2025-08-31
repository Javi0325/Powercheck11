<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('device_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->unsignedBigInteger('athlete_id'); // id del usuario atleta (users.id)
            $table->string('status')->default('active'); // active|ended
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // opcional
            $table->timestamps();

            $table->index(['device_id', 'status']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('device_sessions');
    }
};