<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// La organización autoriza correos y les asigna rol
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizacion_id')->constrained('organizaciones')->cascadeOnDelete();
            $table->string('email');
            $table->enum('rol', ['admin', 'usuario'])->default('usuario');
            $table->foreignId('invitado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aceptada_at')->nullable();
            $table->timestamps();
            $table->unique(['organizacion_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitaciones');
    }
};
