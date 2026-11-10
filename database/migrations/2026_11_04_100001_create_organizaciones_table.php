<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** RF01.1: la organización se registra y configura su información básica. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizaciones', function (Blueprint $table) {
            $table->id();
            // Datos básicos
            $table->string('nombre');
            $table->string('nit', 30)->nullable()->unique();
            $table->string('tipo', 40);
            $table->text('descripcion')->nullable();
            $table->string('ciudad', 80)->nullable();
            $table->string('direccion')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('correo_contacto')->nullable();
            $table->string('sitio_web')->nullable();
            $table->string('logo')->nullable();
            // Acceso de miembros
            $table->string('codigo_invitacion', 12)->unique();
            // Configuración operativa por defecto (RF06)
            $table->time('hora_apertura')->default('07:00');
            $table->time('hora_cierre')->default('19:00');
            $table->json('dias_operativos'); // 1 = lunes ... 7 = domingo
            $table->boolean('cerrar_en_festivos')->default(true);
            $table->boolean('requiere_aprobacion')->default(true);
            $table->unsignedTinyInteger('umbral_subutilizacion')->default(20); // RF24, en %
            // Mientras sea false, nadie más puede unirse (RF01.1)
            $table->boolean('configurada')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizaciones');
    }
};
