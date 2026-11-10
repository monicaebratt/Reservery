<?php

namespace Database\Seeders;

use App\Models\Bloqueo;
use App\Models\ObjetoReservable;
use App\Models\Organizacion;
use App\Models\Reserva;
use App\Models\User;
use App\Services\ListaEspera\ListaEsperaService;
use App\Services\Reservas\ReservaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Datos de demostración. Todas las contraseñas son "password".
 *
 *  - org@reservery.com      cuenta de la organización (rol organizacion)
 *  - admin@reservery.com    administrador
 *  - usuario@reservery.com  usuario
 *  - usuario2@reservery.com usuario
 *  - docente@reservery.com  usuario (pide los eventos institucionales)
 *  - colegio@reservery.com  organización registrada que aún no termina la configuración
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        config(['reservery.notificar_por_correo' => false]);
        mt_srand(2026);

        // ---------- Organización principal (configurada) ----------
        $org = Organizacion::create([
            'nombre' => 'Universidad Demo Reservery',
            'nit' => '900123456-7',
            'tipo' => 'universidad',
            'descripcion' => 'Campus de demostración con auditorios, salas, laboratorios y equipos.',
            'ciudad' => 'Medellín',
            'direccion' => 'Calle 73 # 76A-354',
            'telefono' => '6044405100',
            'correo_contacto' => 'reservas@demo.edu.co',
            'codigo_invitacion' => 'DEMO2026',
            'hora_apertura' => '07:00',
            'hora_cierre' => '21:00',
            'dias_operativos' => [1, 2, 3, 4, 5, 6],
            'cerrar_en_festivos' => true,
            'requiere_aprobacion' => true,
            'umbral_subutilizacion' => 5,
            'configurada' => true,
        ]);

        $cuentaOrg = $this->usuario($org, 'Universidad Demo (cuenta)', 'org@reservery.com', 'organizacion');
        $admin = $this->usuario($org, 'Carolina Admin', 'admin@reservery.com', 'admin');
        $laura = $this->usuario($org, 'Laura Gómez', 'usuario@reservery.com', 'usuario');
        $andres = $this->usuario($org, 'Andrés Restrepo', 'usuario2@reservery.com', 'usuario');
        $docente = $this->usuario($org, 'Prof. Jorge Cárdenas', 'docente@reservery.com', 'usuario');
        $miembros = collect([$laura, $andres, $docente, $this->usuario($org, 'Sofía Mejía', 'sofia@reservery.com', 'usuario')]);

        // RF01.2: un correo autorizado como administrador que aún no crea su cuenta
        $org->invitaciones()->create(['email' => 'nuevo.admin@reservery.com', 'rol' => 'admin', 'invitado_por' => $cuentaOrg->id]);

        $p = $org->propositos()->pluck('id', 'nombre');

        // ---------- Inventario (RF10) ----------
        $o = fn (string $nombre, string $tipo, string $categoria, ?int $cap, string $ubicacion, ?string $zona, ?int $piso, string $desc = '') => tap(
            ObjetoReservable::create([
                'organizacion_id' => $org->id, 'nombre' => $nombre, 'tipo' => $tipo, 'categoria' => $categoria,
                'capacidad' => $cap, 'ubicacion' => $ubicacion, 'zona' => $zona, 'piso' => $piso, 'descripcion' => $desc,
            ]),
            fn ($obj) => $obj->aplicarHorarioPorDefecto()
        );

        $audPrincipal = $o('Auditorio Principal', 'espacio', 'Auditorio', 150, 'Bloque A, piso 1', 'Bloque A', 1, 'Sonido envolvente, tarima y proyector fijo.');
        $audMenor = $o('Auditorio Menor', 'espacio', 'Auditorio', 80, 'Bloque A, piso 2', 'Bloque A', 2, 'Ideal para conferencias medianas.');
        $sala201 = $o('Sala de juntas 201', 'espacio', 'Sala de reuniones', 12, 'Bloque B, piso 2', 'Bloque B', 2, 'Mesa para 12 y pantalla.');
        $sala305 = $o('Sala de juntas 305', 'espacio', 'Sala de reuniones', 14, 'Bloque B, piso 3', 'Bloque B', 3, 'Con tablero inteligente.');
        $salaC101 = $o('Sala C-101', 'espacio', 'Sala de reuniones', 10, 'Bloque C, piso 1', 'Bloque C', 1);
        $lab1 = $o('Laboratorio de sistemas 1', 'espacio', 'Laboratorio', 30, 'Bloque C, piso 3', 'Bloque C', 3, '30 equipos de cómputo.');
        $lab2 = $o('Laboratorio de sistemas 2', 'espacio', 'Laboratorio', 30, 'Bloque C, piso 4', 'Bloque C', 4, '30 equipos de cómputo.');
        $cancha = $o('Cancha múltiple', 'espacio', 'Cancha', 40, 'Zona deportiva', 'Zona deportiva', 0, 'Fútbol sala, baloncesto y voleibol.');

        $vb1 = $o('Videobeam Epson #1', 'recurso', 'Proyector', null, 'Almacén de equipos, Bloque A', 'Bloque A', 1);
        $vb2 = $o('Videobeam Epson #2', 'recurso', 'Proyector', null, 'Almacén de equipos, Bloque A', 'Bloque A', 1);
        $vb3 = $o('Videobeam Epson #3', 'recurso', 'Proyector', null, 'Almacén de equipos, Bloque C', 'Bloque C', 1);
        $pc1 = $o('Portátil Lenovo #1', 'recurso', 'Portátil', null, 'Almacén de equipos, Bloque B', 'Bloque B', 1);
        $pc2 = $o('Portátil Lenovo #2', 'recurso', 'Portátil', null, 'Almacén de equipos, Bloque B', 'Bloque B', 1);
        $cam = $o('Cámara Canon EOS', 'recurso', 'Cámara', null, 'Oficina de comunicaciones', 'Bloque A', 2);
        $mic1 = $o('Micrófono inalámbrico #1', 'recurso', 'Sonido', null, 'Almacén de equipos, Bloque A', 'Bloque A', 1);
        $mic2 = $o('Micrófono inalámbrico #2', 'recurso', 'Sonido', null, 'Almacén de equipos, Bloque A', 'Bloque A', 1);
        $kit = $o('Kit de balones', 'recurso', 'Implementos deportivos', null, 'Zona deportiva', 'Zona deportiva', 0);

        // RF06: la cancha abre todos los días, más temprano y más tarde
        foreach (range(1, 7) as $d) {
            $cancha->horarios()->updateOrCreate(['dia_semana' => $d], ['hora_inicio' => '06:00', 'hora_fin' => '22:00']);
        }

        // RF14: paquetes preaprobados (qué recursos van con cada espacio)
        $audPrincipal->recursosAsociados()->sync([$vb1->id, $vb2->id, $mic1->id, $mic2->id, $cam->id]);
        $audMenor->recursosAsociados()->sync([$vb1->id, $vb2->id, $mic1->id, $mic2->id, $cam->id]);
        foreach ([$sala201, $sala305, $salaC101] as $sala) {
            $sala->recursosAsociados()->sync([$vb1->id, $vb2->id, $vb3->id, $pc1->id, $pc2->id]);
        }
        foreach ([$lab1, $lab2] as $lab) {
            $lab->recursosAsociados()->sync([$vb2->id, $vb3->id, $pc1->id, $pc2->id]);
        }
        $cancha->recursosAsociados()->sync([$kit->id]);

        // ---------- Historial de los últimos 60 días (para la analítica, RF23–RF25) ----------
        // Paquetes frecuentes; la Sala C-101 y el kit de balones casi no se usan (aparecen como subutilizados, RF24)
        $espaciosHistoricos = [
            [$audPrincipal, $vb1, $mic1], [$audPrincipal, $mic2], [$audMenor, $vb2], [$sala201, $vb1], [$sala201, $pc1], [$sala201],
            [$sala305, $vb3], [$sala305, $pc2], [$lab1, $vb3], [$lab1], [$lab2, $pc1], [$cancha], [$audMenor, $cam],
        ];
        $franjas = [['08:00', '10:00'], ['08:00', '10:00'], ['08:00', '11:00'], ['10:00', '12:00'], ['14:00', '16:00'], ['14:00', '17:00'], ['16:00', '18:00'], ['18:00', '20:00'], ['07:00', '09:00']];
        $motivos = ['Clase de programación', 'Reunión de comité', 'Sustentación', 'Taller de investigación', 'Práctica deportiva', 'Charla invitada', 'Estudio en grupo'];

        for ($i = 0; $i < 420; $i++) {
            $fecha = today()->subDays(mt_rand(1, 60));
            if ($fecha->isSunday()) {
                continue;
            }
            $paquete = $espaciosHistoricos[array_rand($espaciosHistoricos)];
            [$ini, $fin] = $franjas[array_rand($franjas)];
            $choca = Reserva::whereDate('fecha', $fecha)->where('hora_inicio', '<', $fin)->where('hora_fin', '>', $ini)
                ->whereHas('objetos', fn ($q) => $q->whereIn('objetos_reservables.id', collect($paquete)->pluck('id')))->exists();
            if ($choca) {
                continue;
            }

            $estado = mt_rand(1, 100) <= 80 ? 'completada' : (mt_rand(0, 1) ? 'cancelada' : 'rechazada');
            $r = Reserva::create([
                'organizacion_id' => $org->id,
                'user_id' => $miembros->random()->id,
                'proposito_id' => $p->random(),
                'fecha' => $fecha->toDateString(),
                'hora_inicio' => $ini, 'hora_fin' => $fin,
                'motivo' => $motivos[array_rand($motivos)],
                'estado' => $estado,
            ]);
            $r->objetos()->sync(collect($paquete)->pluck('id'));
            $r->historial()->create(['accion' => 'creada', 'estado_nuevo' => 'pendiente', 'responsable_id' => $r->user_id, 'created_at' => $fecha->copy()->subDays(3)]);
            $r->historial()->create(['accion' => $estado, 'estado_anterior' => 'pendiente', 'estado_nuevo' => $estado, 'responsable_id' => $admin->id, 'created_at' => $fecha]);
        }

        // ---------- Reservas próximas (pasan por las reglas reales del sistema) ----------
        $servicio = app(ReservaService::class);
        $dia = fn (int $n) => $this->diaHabil($n);
        $reservar = fn (User $u, array $objetos, string $proposito, Carbon $fecha, string $ini, string $fin, string $motivo) => $servicio->solicitar($u, [
            'proposito_id' => $p[$proposito], 'fecha' => $fecha->toDateString(), 'hora_inicio' => $ini, 'hora_fin' => $fin,
            'motivo' => $motivo, 'objetos' => collect($objetos)->pluck('id')->all(),
        ]);

        // RF13: reserva compuesta (espacio + recursos)
        $r1 = $reservar($andres, [$audPrincipal, $vb1, $mic1], 'Clase programada', $dia(2), '14:00', '16:00', 'Clase magistral de Ingeniería de Software');
        $r1->aprobar($admin);
        $reservar($laura, [$sala201, $vb2], 'Reunión de trabajo', $dia(1), '10:00', '12:00', 'Reunión del semillero de investigación');
        $r3 = $reservar($laura, [$lab1], 'Uso individual', $dia(3), '08:00', '10:00', 'Práctica de bases de datos');
        $r3->aprobar($admin);
        $reservar($andres, [$sala305, $pc1], 'Reunión de trabajo', $dia(1), '15:00', '17:00', 'Planeación del proyecto de grado');
        $r5 = $reservar($laura, [$cancha, $kit], 'Uso individual', $dia(4), '17:00', '18:00', 'Partido amistoso');
        $r5->rechazar($admin, 'La cancha está reservada para el torneo interno.');
        $r6 = $reservar($docente, [$audMenor], 'Evento institucional', $dia(5), '09:00', '11:00', 'Lanzamiento de semillero');
        $r6->aprobar($admin);
        $r6->cancelar($docente, 'Se aplazó el evento.');

        // RF18: un evento institucional desplaza el "uso individual" de Laura en el laboratorio 1
        $reservar($docente, [$lab1, $vb3], 'Evento institucional', $dia(3), '08:00', '12:00', 'Hackatón institucional');

        // RF07: bloqueo por mantenimiento
        Bloqueo::create([
            'organizacion_id' => $org->id, 'objeto_reservable_id' => $lab2->id, 'creado_por' => $admin->id,
            'fecha_inicio' => $dia(2)->setTime(7, 0), 'fecha_fin' => $dia(2)->setTime(13, 0),
            'motivo' => 'Mantenimiento de equipos',
        ]);

        // RF22: Laura queda en lista de espera del auditorio principal ya reservado
        app(ListaEsperaService::class)->inscribir($laura, [
            'proposito_id' => $p['Reunión de trabajo'], 'objetos' => [$audPrincipal->id],
            'fecha' => $dia(2)->toDateString(), 'hora_inicio' => '14:00', 'hora_fin' => '16:00',
            'motivo' => 'Asamblea de estudiantes',
        ]);

        // ---------- Otra organización (para mostrar que los datos no se mezclan) ----------
        $pinos = Organizacion::create([
            'nombre' => 'Conjunto Residencial Los Pinos', 'tipo' => 'conjunto', 'ciudad' => 'Envigado',
            'correo_contacto' => 'administracion@lospinos.co', 'codigo_invitacion' => 'PINOS123',
            'dias_operativos' => [1, 2, 3, 4, 5, 6, 7], 'hora_apertura' => '08:00', 'hora_cierre' => '22:00',
            'requiere_aprobacion' => false, 'configurada' => true,
        ]);
        $this->usuario($pinos, 'Administración Los Pinos', 'pinos@reservery.com', 'organizacion');
        $salon = ObjetoReservable::create(['organizacion_id' => $pinos->id, 'nombre' => 'Salón social', 'tipo' => 'espacio', 'categoria' => 'Salón', 'capacidad' => 60, 'ubicacion' => 'Torre 1']);
        $salon->aplicarHorarioPorDefecto();
        ObjetoReservable::create(['organizacion_id' => $pinos->id, 'nombre' => 'BBQ', 'tipo' => 'espacio', 'categoria' => 'Zona social', 'capacidad' => 20, 'ubicacion' => 'Terraza'])->aplicarHorarioPorDefecto();

        // ---------- Organización registrada que aún no habilita el acceso (RF01.1) ----------
        $colegio = Organizacion::create([
            'nombre' => 'Colegio Nuevo Horizonte', 'tipo' => 'colegio', 'correo_contacto' => 'rectoria@nuevohorizonte.edu.co',
            'codigo_invitacion' => 'COLEGIO1', 'configurada' => false,
        ]);
        $this->usuario($colegio, 'Rectoría Nuevo Horizonte', 'colegio@reservery.com', 'organizacion');
    }

    private function usuario(Organizacion $org, string $nombre, string $email, string $rol): User
    {
        return User::create([
            'organizacion_id' => $org->id, 'name' => $nombre, 'email' => $email,
            'password' => 'password', 'rol' => $rol, 'email_verified_at' => now(),
        ]);
    }

    /** El n-ésimo día de lunes a sábado (sin festivos) a partir de mañana. */
    private function diaHabil(int $n): Carbon
    {
        $festivos = app(\App\Services\FestivosService::class);
        $dia = today();
        while ($n > 0) {
            $dia->addDay();
            if (! $dia->isSunday() && ! $festivos->esFestivo($dia->toDateString())) {
                $n--;
            }
        }

        return $dia->copy();
    }
}
