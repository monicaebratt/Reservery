<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ObjetoReservable (clase abstracta del diagrama). Espacio y Recurso se
 * distinguen por la columna "tipo" (herencia de tabla única).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objetos_reservables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizacion_id')->constrained('organizaciones')->cascadeOnDelete();
            $table->string('nombre');
            $table->enum('tipo', ['espacio', 'recurso']);
            $table->string('categoria', 60); // ej. Auditorio, Sala, Proyector: define qué es "equivalente" (RF19)
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('capacidad')->nullable();
            $table->string('ubicacion')->nullable();
            $table->string('zona', 60)->nullable(); // sede o bloque: define "ubicación cercana" (RF19)
            $table->smallInteger('piso')->nullable();
            $table->string('imagen')->nullable();
            $table->boolean('disponible')->default(true);
            $table->timestamps();
            $table->index(['organizacion_id', 'tipo', 'categoria']);
        });

        // RF14: qué recursos se pueden reservar junto con cada espacio (paquetes)
        Schema::create('espacio_recurso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('espacio_id')->constrained('objetos_reservables')->cascadeOnDelete();
            $table->foreignId('recurso_id')->constrained('objetos_reservables')->cascadeOnDelete();
            $table->unique(['espacio_id', 'recurso_id']);
        });

        // RF06: rango de horario operativo por día de la semana
        Schema::create('horarios_operativos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objeto_reservable_id')->constrained('objetos_reservables')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana'); // 1 = lunes ... 7 = domingo
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->unique(['objeto_reservable_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_operativos');
        Schema::dropIfExists('espacio_recurso');
        Schema::dropIfExists('objetos_reservables');
    }
};
