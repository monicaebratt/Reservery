{{-- Portada pública. Hereda de la maestra base. --}}
@extends('layouts.base')

@section('titulo', 'Reserva espacios y equipos')

@push('estilos')
<style>
    .portada-nav { display: flex; align-items: center; gap: 1rem; padding: 1.4rem 0; }
    .hero { padding: 2.5rem 0 4.5rem; }
    .hero h1 { font-family: 'Marcellus', Georgia, serif; font-size: clamp(2.6rem, 5.4vw, 4.4rem); line-height: 1.04; max-width: 13ch; margin-bottom: 1.25rem; }
    .hero p.lead { color: var(--rv-tenue); max-width: 46ch; font-size: 1.12rem; line-height: 1.6; }

    /* El detalle memorable: la órbita del logo rodeando una agenda real */
    .escena { position: relative; aspect-ratio: 1 / .78; }
    .escena .orbita { position: absolute; inset: 6% -4% 12% -4%; border: 2px solid var(--rv-lila); border-radius: 50%; transform: rotate(-16deg); opacity: .7; }
    .escena .destello { position: absolute; width: 34px; top: 4%; right: 12%; color: var(--rv-violeta); animation: brillo 3.2s ease-in-out 1; }
    .escena .destello.chico { width: 18px; top: auto; bottom: 14%; left: 8%; right: auto; color: var(--rv-lila); }
    @keyframes brillo { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.25) rotate(20deg); } }
    .agenda { position: absolute; top: 18%; left: 11%; right: 11%; background: #fff; border: 1px solid var(--rv-linea); border-radius: 22px; padding: 1.2rem 1.3rem; display: flex; flex-direction: column; gap: .6rem; }
    .agenda .cab { display: flex; justify-content: space-between; align-items: baseline; }
    .agenda .cab strong { font-family: 'Marcellus', serif; font-weight: 400; font-size: 1.25rem; }
    .fila { display: grid; grid-template-columns: 44px 1fr; gap: .7rem; align-items: center; font-size: .84rem; }
    .fila .hora { color: var(--rv-tenue); }
    .bloque { border-radius: 10px; padding: .45rem .7rem; }
    .bloque.ok { background: var(--rv-violeta); color: #fff; }
    .bloque.pend { background: var(--rv-panel); color: var(--rv-profundo); }
    .bloque.libre { border: 1px dashed var(--rv-lila); color: var(--rv-violeta); }

    .rasgos { padding: 4rem 0; border-top: 1px solid var(--rv-linea); }
    .rasgo h2 { font-family: 'Marcellus', serif; font-size: 1.35rem; margin-bottom: .5rem; }
    .rasgo p { color: var(--rv-tenue); margin: 0; max-width: 34ch; }
    .rasgo svg { width: 18px; color: var(--rv-violeta); margin-bottom: .9rem; }

    .pasos { background: var(--rv-panel); border-radius: 28px; padding: 3rem 2rem; }
    .pasos h2 { font-family: 'Marcellus', serif; font-size: clamp(1.8rem, 3vw, 2.4rem); }
    .paso-num { width: 2.4rem; height: 2.4rem; border-radius: 50%; display: grid; place-items: center; background: #fff; color: var(--rv-violeta); font-weight: 700; }
    .pasos p { color: var(--rv-tenue); max-width: 30ch; }
</style>
@endpush

@php($destello = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0c.6 6.4 5.6 11.4 12 12-6.4.6-11.4 5.6-12 12-.6-6.4-5.6-11.4-12-12C6.4 11.4 11.4 6.4 12 0z"/></svg>')

@section('cuerpo')
<div class="container">
    <nav class="portada-nav">
        <a href="{{ route('inicio') }}" class="marca" aria-label="Reservery">
            <img src="{{ asset('img/logo-icono.png') }}" alt="" class="icono">
            <img src="{{ asset('img/logo-texto.png') }}" alt="Reservery" class="texto">
        </a>
        <div class="ms-auto d-flex gap-2">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Ir a mi panel</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-light">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="btn btn-primary d-none d-sm-inline-block">Crear cuenta</a>
            @endauth
        </div>
    </nav>

    <header class="hero">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h1>Reservar no debería ser un enredo.</h1>
                <p class="lead mb-4">Salas, auditorios, laboratorios y equipos de tu organización en un solo lugar. Ves qué está libre, lo pides y el sistema evita los cruces de horario.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('organizacion.create') }}" class="btn btn-primary btn-lg">Registrar mi organización</a>
                    <a href="{{ route('register') }}" class="btn btn-light btn-lg">Tengo un código</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="escena" aria-hidden="true">
                    <div class="orbita"></div>
                    <span class="destello">{!! $destello !!}</span>
                    <span class="destello chico">{!! $destello !!}</span>
                    <div class="agenda">
                        <div class="cab"><strong>Sala de juntas 201</strong><small class="text-muted">{{ now()->translatedFormat('l d') }}</small></div>
                        <div class="fila"><span class="hora">08:00</span><div class="bloque ok">Comité académico</div></div>
                        <div class="fila"><span class="hora">10:00</span><div class="bloque libre">Libre</div></div>
                        <div class="fila"><span class="hora">12:00</span><div class="bloque pend">Semillero · por aprobar</div></div>
                        <div class="fila"><span class="hora">14:00</span><div class="bloque libre">Libre</div></div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="rasgos">
        <div class="row g-5">
            <div class="col-md-4 rasgo">
                {!! $destello !!}
                <h2>Lo que está libre, al momento</h2>
                <p>La agenda se actualiza sola. Si algo ya está ocupado, te proponemos uno equivalente o te avisamos cuando se libere.</p>
            </div>
            <div class="col-md-4 rasgo">
                {!! $destello !!}
                <h2>Espacio y equipos juntos</h2>
                <p>Pide la sala con su videobeam o sus micrófonos en una sola solicitud. Solo se aprueba si todo está disponible.</p>
            </div>
            <div class="col-md-4 rasgo">
                {!! $destello !!}
                <h2>Reglas claras para todos</h2>
                <p>Cada organización define sus horarios, sus prioridades y quién aprueba. Todo queda registrado.</p>
            </div>
        </div>
    </section>

    <section class="pasos mb-5">
        <h2 class="mb-4">Empezar toma tres pasos</h2>
        <div class="row g-4">
            @foreach ([
                ['Registra tu organización', 'Configura tus horarios y espacios, y habilita el acceso.'],
                ['Invita a tu gente', 'Comparte el código o autoriza sus correos. Pueden entrar con Google.'],
                ['Empiecen a reservar', 'Los miembros piden y los administradores aprueban desde su panel.'],
            ] as $i => [$titulo, $texto])
                <div class="col-md-4">
                    <div class="paso-num mb-2">{{ $i + 1 }}</div>
                    <h3 class="h6 fw-bold">{{ $titulo }}</h3>
                    <p class="mb-0">{{ $texto }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <footer class="d-flex flex-wrap justify-content-between gap-2 small text-muted py-4">
        <span>© {{ date('Y') }} Reservery</span>
        <span>Tus espacios y equipos, siempre en orden.</span>
    </footer>
</div>
@endsection
