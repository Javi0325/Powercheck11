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
        Schema::create('gimnasios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('ubicacion');
            $table->string('logo')->nullable()->default('gym_logos/default_logo.png');
            $table->string('celular', 15)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gimnasios');
    }
};
