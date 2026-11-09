<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** RF18: una reserva de mayor prioridad propone reemplazar a otra. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reemplazos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_prioritaria_id')->constrained('reservas')->cascadeOnDelete();
            $table->foreignId('reserva_afectada_id')->constrained('reservas')->cascadeOnDelete();
            $table->enum('estado', ['propuesto', 'alternativa_aceptada', 'sin_alternativa', 'confirmado', 'descartado'])
                ->default('propuesto');
            $table->json('alternativas')->nullable();      // lo que se le ofreció al afectado
            $table->json('alternativa_elegida')->nullable(); // ids de objetos que eligió
            $table->timestamp('respondido_at')->nullable();
            $table->timestamps();
            $table->unique(['reserva_prioritaria_id', 'reserva_afectada_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reemplazos');
    }
};
