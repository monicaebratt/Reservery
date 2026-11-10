<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** RF22: lista de espera por orden de llegada. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lista_espera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizacion_id')->constrained('organizaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('objeto_reservable_id')->constrained('objetos_reservables')->cascadeOnDelete(); // objeto principal pedido
            $table->foreignId('proposito_id')->constrained('propositos')->restrictOnDelete();
            $table->json('objetos'); // el paquete completo pedido (espacio + recursos)
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('motivo');
            $table->enum('estado', ['esperando', 'notificada', 'atendida', 'cancelada', 'vencida'])->default('esperando');
            $table->timestamp('notificada_at')->nullable();
            $table->foreignId('reserva_id')->nullable()->constrained('reservas')->nullOnDelete();
            $table->timestamps(); // created_at = fecha de inscripción
            $table->index(['objeto_reservable_id', 'fecha', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lista_espera');
    }
};
