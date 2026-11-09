<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organizacion_id')->nullable()->after('id')
                ->constrained('organizaciones')->nullOnDelete();
            // organizacion = cuenta que registró la organización; admin; usuario (RF12)
            $table->enum('rol', ['organizacion', 'admin', 'usuario'])->default('usuario')->after('password');
            $table->string('telefono', 30)->nullable()->after('rol');
            $table->string('foto')->nullable()->after('telefono');
            $table->string('google_id')->nullable()->unique()->after('foto');
            // Quien entra con Google puede no tener contraseña
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organizacion_id');
            $table->dropColumn(['rol', 'telefono', 'foto', 'google_id']);
        });
    }
};
