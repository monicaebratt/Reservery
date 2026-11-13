@extends('layouts.base')

@section('titulo', 'Gestiona tus espacios')

@push('estilos')
<style>
    html { scroll-behavior: smooth; }
    .portada-nav { display: flex; align-items: center; gap: 1.5rem; padding: 1.6rem 0; }
    .portada-nav .marca .icono { height: 46px; width: auto; }
    .portada-nav .marca .texto { height: 38px; width: auto; }
    .enlaces { display: flex; gap: 2.6rem; margin: 0 auto; }
    .enlaces a { color: var(--rv-profundo); text-decoration: none; font-weight: 500; padding: .35rem .2rem; border-bottom: 2px solid transparent; }
    .enlaces a:hover, .enlaces a.activo { border-bottom-color: var(--rv-violeta); }
    .btn-sesion { background: rgba(255,255,255,.75); border: 1px solid rgba(255,255,255,.95); color: var(--rv-profundo); font-weight: 600; padding: .65rem 1.4rem; box-shadow: 0 8px 22px -14px rgba(76,52,140,.5); }
    .btn-sesion:hover { background: #fff; color: var(--rv-profundo); }

    .hero { min-height: calc(100vh - 120px); display: flex; align-items: center; padding: 1rem 0 6rem; }
    .hero h1 { font-family: 'Marcellus', Georgia, serif; font-size: clamp(2.7rem, 5vw, 4.6rem); line-height: 1.06; color: var(--rv-profundo);
        text-shadow: 0 2px 18px rgba(255,255,255,.7); max-width: 12.5ch; margin-bottom: 1.4rem; }
    .hero p.lead { color: #5E5385; font-size: clamp(1.05rem, 1.4vw, 1.25rem); line-height: 1.6; max-width: 34ch; margin-bottom: 2.2rem; }
    .hero .btn { padding: .9rem 1.9rem; font-weight: 600; }
    .hero-escena { min-height: 100vh; }
    .hero-escena::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 160px; z-index: 0; pointer-events: none; background: linear-gradient(180deg, rgba(244,240,255,0) 0%, #F4F0FF 100%); }
    .hero .calendario-3d { width: 118%; max-width: 900px; margin-left: -6%; }
    .portada-resto { background: linear-gradient(180deg, #F4F0FF 0%, #F8F6FF 100%); position: relative; }
    .portada-resto .vidrio { background: rgba(255,255,255,.75); }
    .raton { position: absolute; left: 0; bottom: 1.5rem; display: flex; flex-direction: column; align-items: center; gap: .35rem; color: var(--rv-violeta); text-decoration: none; }
    .raton span { width: 22px; height: 36px; border: 2px solid currentColor; border-radius: 12px; position: relative; }
    .raton span::after { content: ""; position: absolute; left: 50%; top: 7px; width: 3px; height: 7px; border-radius: 2px; background: currentColor; transform: translateX(-50%); animation: rueda 1.8s ease-in-out infinite; }
    @keyframes rueda { 0% { opacity: 1; top: 7px; } 100% { opacity: 0; top: 18px; } }

    .seccion { padding: 5rem 0; }
    .seccion h2 { font-family: 'Marcellus', Georgia, serif; font-size: clamp(2rem, 3.4vw, 2.8rem); color: var(--rv-profundo); }
    .seccion .sub { color: #5E5385; max-width: 52ch; }
    .funcion { padding: 2rem; height: 100%; }
    .funcion .circulo { width: 58px; height: 58px; border-radius: 50%; display: grid; place-items: center; font-size: 1.5rem; color: var(--rv-violeta);
        background: linear-gradient(135deg, #F4EFFF, #E3D7FD); margin-bottom: 1.2rem; }
    .funcion h3 { font-family: 'Marcellus', Georgia, serif; font-size: 1.35rem; color: var(--rv-profundo); }
    .funcion p { color: #5E5385; margin: 0; }
    .paso-num { width: 2.6rem; height: 2.6rem; border-radius: 50%; display: grid; place-items: center; font-weight: 700; color: #fff; background: linear-gradient(135deg, #8F70D8, #7458BE); margin-bottom: .9rem; }
    .portada-pie { padding: 2rem 0 2.5rem; color: #5E5385; }
    @media (max-width: 575px) { .portada-nav .marca .icono { height: 34px; } .portada-nav .marca .texto { height: 28px; } .btn-sesion { white-space: nowrap; padding: .5rem 1rem; } }
    @media (max-width: 991px) { .enlaces { display: none; } .hero { min-height: 0; padding: 1rem 0 4rem; } .raton { display: none; } }
</style>
@endpush

@section('cuerpo')
<div class="escenario hero-escena">
    @include('partials.escenario')

    <div class="container position-relative">
        <nav class="portada-nav">
            <a href="{{ route('inicio') }}" class="marca" aria-label="Reservery">
                <img src="{{ asset('img/logo-icono.png') }}" alt="" class="icono">
                <img src="{{ asset('img/logo-texto.png') }}" alt="Reservery" class="texto">
            </a>
            <div class="enlaces">
                <a href="#inicio" class="activo">Inicio</a>
                <a href="#funciones">Funciones</a>
                <a href="#como-empezar">Cómo empezar</a>
            </div>
            <div class="ms-auto ms-lg-0">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-sesion">Ir a mi panel</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sesion">Iniciar sesión</a>
                @endauth
            </div>
        </nav>

        <header class="hero" id="inicio">
            <div class="row align-items-center g-5 w-100 m-0">
                <div class="col-lg-6 ps-lg-0">
                    <h1>Gestiona tus espacios de forma simple y eficiente</h1>
                    <p class="lead">Reserva, organiza y administra tus espacios con Reservery, el sistema que se adapta a ti.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('organizacion.create') }}" class="btn btn-brillo btn-lg d-inline-flex align-items-center gap-2">Comenzar ahora <i class="bi bi-arrow-right"></i></a>
                        <a href="#funciones" class="btn btn-vidrio btn-lg">Conocer más</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="flota-suave"><img src="{{ asset('img/calendario-3d.webp') }}" alt="Calendario de Reservery con una reserva destacada y una solicitud aprobada" class="calendario-3d" width="1200" height="515"></div>
                </div>
            </div>
            <a href="#funciones" class="raton" aria-label="Bajar a las funciones"><span></span><i class="bi bi-chevron-down"></i></a>
        </header>
    </div>
</div>

<div class="portada-resto">
    <div class="container">

        <section class="seccion" id="funciones">
            <h2 class="mb-2">Todo lo que necesitas para reservar sin enredos</h2>
            <p class="sub mb-5">Salas, auditorios, laboratorios y equipos de tu organización en un solo lugar.</p>
            <div class="row g-4">
                @foreach ([
                    ['bi-calendar-check', 'Lo que está libre, al momento', 'La agenda se actualiza sola. Si algo ya está ocupado, te proponemos uno equivalente o te avisamos cuando se libere.'],
                    ['bi-box-seam', 'Espacio y equipos juntos', 'Pide la sala con su videobeam o sus micrófonos en una sola solicitud. Solo se aprueba si todo está disponible.'],
                    ['bi-shield-check', 'Reglas claras para todos', 'Cada organización define sus horarios, sus prioridades y quién aprueba. Todo queda registrado.'],
                ] as [$icono, $titulo, $texto])
                    <div class="col-md-4">
                        <div class="vidrio funcion">
                            <span class="circulo"><i class="bi {{ $icono }}"></i></span>
                            <h3>{{ $titulo }}</h3>
                            <p>{{ $texto }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="seccion pt-2" id="como-empezar">
            <div class="vidrio p-4 p-lg-5">
                <h2 class="mb-4">Empezar toma tres pasos</h2>
                <div class="row g-4">
                    @foreach ([
                        ['Registra tu organización', 'Configura tus horarios y espacios, y habilita el acceso.'],
                        ['Invita a tu gente', 'Comparte el código o autoriza sus correos. Pueden entrar con Google.'],
                        ['Empiecen a reservar', 'Los miembros piden y los administradores aprueban desde su panel.'],
                    ] as $i => [$titulo, $texto])
                        <div class="col-md-4">
                            <div class="paso-num">{{ $i + 1 }}</div>
                            <h3 class="h5 fw-bold" style="color: var(--rv-profundo)">{{ $titulo }}</h3>
                            <p class="mb-0" style="color: #5E5385">{{ $texto }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="{{ route('organizacion.create') }}" class="btn btn-brillo">Registrar mi organización</a>
                    <a href="{{ route('register') }}" class="btn btn-vidrio">Tengo un código</a>
                </div>
            </div>
        </section>

        <footer class="portada-pie d-flex flex-wrap justify-content-between gap-2 small">
            <span>© {{ date('Y') }} Reservery</span>
            <span>Tus espacios y equipos, siempre en orden.</span>
        </footer>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Resalta en el menú la sección que se está viendo
    const enlaces = document.querySelectorAll('.enlaces a');
    const observador = new IntersectionObserver((entradas) => entradas.forEach(e => {
        if (e.isIntersecting) enlaces.forEach(a => a.classList.toggle('activo', a.getAttribute('href') === '#' + e.target.id));
    }), { rootMargin: '-45% 0px -50% 0px' });
    ['inicio', 'funciones', 'como-empezar'].forEach(id => observador.observe(document.getElementById(id)));
</script>
@endpush
