<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** RF11: notificaciones automáticas a los involucrados. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reserva_id')->nullable()->constrained('reservas')->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->string('titulo');
            $table->text('mensaje');
            $table->string('url')->nullable();
            $table->boolean('leida')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'leida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
