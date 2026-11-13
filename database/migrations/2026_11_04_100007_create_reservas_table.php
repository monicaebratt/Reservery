<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizacion_id')->constrained('organizaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposito_id')->constrained('propositos')->restrictOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('motivo');
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada', 'cancelada', 'completada'])
                ->default('pendiente');
            $table->timestamps();
            $table->index(['organizacion_id', 'fecha', 'estado']);
        });

        // Reserva N:M ObjetoReservable (una reserva compuesta incluye espacio + recursos)
        Schema::create('objeto_reservable_reserva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained('reservas')->cascadeOnDelete();
            $table->foreignId('objeto_reservable_id')->constrained('objetos_reservables')->cascadeOnDelete();
            $table->unique(['reserva_id', 'objeto_reservable_id']);
        });

        // Trazabilidad de cada cambio con fecha, hora y responsable
        Schema::create('historial_reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained('reservas')->cascadeOnDelete();
            $table->string('accion', 40);
            $table->string('estado_anterior', 20)->nullable();
            $table->string('estado_nuevo', 20)->nullable();
            $table->text('detalle')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_reservas');
        Schema::dropIfExists('objeto_reservable_reserva');
        Schema::dropIfExists('reservas');
    }
};
