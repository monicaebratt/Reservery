<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Propósito de la reserva con su nivel de prioridad
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propositos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizacion_id')->constrained('organizaciones')->cascadeOnDelete();
            $table->string('nombre', 80);
            $table->string('descripcion')->nullable();
            $table->unsignedTinyInteger('nivel_prioridad');  // Mayor número = mayor prioridad
            $table->string('color', 7)->default('#4f46e5');
            $table->timestamps();
            $table->unique(['organizacion_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propositos');
    }
};
