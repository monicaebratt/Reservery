{{-- Plantilla común de las páginas de error: la mascota calendario de Reservery con el código --}}
@extends('layouts.base')

@section('titulo', $titulo)

@php($inicio = auth()->check() ? url('/dashboard') : url('/'))

@push('estilos')
<style>
    .error-pagina { position: relative; min-height: 100vh; display: flex; flex-direction: column; overflow: hidden; background: linear-gradient(180deg, #FBFAFF 0%, #F6F1FF 100%); }
    .error-cabecera { position: relative; z-index: 2; display: flex; align-items: center; justify-content: space-between; padding: 1.6rem 2.5rem; }
    .error-cabecera .marca .icono { width: 46px; }
    .error-cabecera .marca .texto { width: 118px; }
    .pildora-inicio { display: inline-flex; align-items: center; gap: .5rem; background: var(--rv-panel); color: var(--rv-profundo); border-radius: 999px; padding: .6rem 1.2rem; font-weight: 600; text-decoration: none; }
    .pildora-inicio:hover { background: #E2D6FF; color: var(--rv-profundo); }
    .pildora-inicio i { color: var(--rv-violeta); }

    .error-centro { position: relative; z-index: 2; flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 0 1.25rem 4rem; }
    .ilustracion { width: min(480px, 92vw); height: auto; margin-bottom: .75rem; animation: flotar 4s ease-in-out infinite;
    }
    /* Ilustraciones en PNG con fondo transparente */
    .ilustracion.cuadrada { width: auto; height: min(300px, 36vh); max-width: 86vw; }
    .mascota { width: min(380px, 86vw); height: auto; margin-bottom: .5rem; }
    .mascota .flota { animation: flotar 4s ease-in-out infinite; transform-origin: 185px 150px; }
    @keyframes flotar { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    .error-centro h1 { font-family: 'Marcellus', Georgia, serif; font-size: clamp(2rem, 4.4vw, 3rem); line-height: 1.12; max-width: 18ch; margin: 0 0 .8rem; }
    .error-centro p { color: var(--rv-tenue); font-size: 1.08rem; max-width: 44ch; margin-bottom: 1.6rem; }
    .btn-error { display: inline-flex; align-items: center; gap: .6rem; padding: .85rem 1.8rem; font-size: 1.05rem; }
    .separador-destello { display: flex; align-items: center; gap: .9rem; width: min(440px, 80vw); margin: 2rem 0 1rem; color: var(--rv-lila); }
    .separador-destello::before, .separador-destello::after { content: ""; flex: 1; border-top: 1px solid var(--rv-linea); }
    .separador-destello svg { width: 18px; }
    .enlace-explorar { color: var(--rv-profundo); font-weight: 500; text-decoration: none; display: inline-flex; gap: .4rem; align-items: center; }
    .enlace-explorar:hover { color: var(--rv-violeta); }

    /* Nubes y órbitas de fondo */
    .nube { position: absolute; bottom: -60px; width: 420px; height: 220px; z-index: 1; pointer-events: none; }
    .nube::before, .nube::after { content: ""; position: absolute; border-radius: 50%; background: #EBE1FF; }
    .nube.izq { left: -90px; } .nube.der { right: -90px; transform: scaleX(-1); }
    .nube::before { width: 300px; height: 300px; left: 0; bottom: -120px; }
    .nube::after { width: 230px; height: 230px; left: 190px; bottom: -130px; background: #F1EBFF; }
    .arco { position: absolute; z-index: 1; border: 1.5px solid #D9CBFA; border-radius: 50%; pointer-events: none; }
    .arco.a1 { width: 520px; height: 520px; left: -260px; bottom: -330px; }
    .arco.a2 { width: 700px; height: 700px; right: -420px; bottom: -380px; }
    .chispa-fondo { position: absolute; z-index: 1; color: #A887E8; }
    @media (max-width: 575px) { .error-cabecera { padding: 1.1rem 1rem; } .pildora-inicio span { display: none; } .nube { width: 260px; } .arco, .chispa-fondo { display: none; } }
    @media (prefers-reduced-motion: reduce) { .mascota .flota, .ilustracion { animation: none; } }
</style>
@endpush

@php($chispa = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0c.6 6.4 5.6 11.4 12 12-6.4.6-11.4 5.6-12 12-.6-6.4-5.6-11.4-12-12C6.4 11.4 11.4 6.4 12 0z"/></svg>')

@section('cuerpo')
<div class="error-pagina">
    <header class="error-cabecera">
        <a href="{{ url('/') }}" class="marca" aria-label="Reservery">
            <img src="{{ asset('img/logo-icono.png') }}" alt="" class="icono">
            <img src="{{ asset('img/logo-texto.png') }}" alt="Reservery" class="texto">
        </a>
        <a href="{{ $inicio }}" class="pildora-inicio"><i class="bi bi-house"></i><span>Volver al inicio</span></a>
    </header>

    <main class="error-centro">
        @isset($imagen)
            {{-- Ilustración de la mascota de Reservery --}}
            <img src="{{ asset($imagen) }}" alt="{{ $descripcion ?? 'La mascota de Reservery con el número '.$codigo }}" class="ilustracion {{ $cuadrada ?? false ? 'cuadrada' : '' }}">
        @else
        {{-- Mascota: el calendario de Reservery, confundido, sosteniendo el código --}}
        <svg class="mascota" viewBox="0 0 370 300" role="img" aria-label="Error {{ $codigo }}">
            <defs>
                <linearGradient id="gCuerpo" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFFFFF"/><stop offset="1" stop-color="#EDE5FF"/></linearGradient>
                <linearGradient id="gBanda" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#C9B2F5"/><stop offset="1" stop-color="#9A7BDD"/></linearGradient>
                <linearGradient id="gNum" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#9677D6"/><stop offset="1" stop-color="#5B3FA8"/></linearGradient>
                <clipPath id="cCuerpo"><rect x="112" y="44" width="150" height="168" rx="24"/></clipPath>
            </defs>

            {{-- Sombra y órbita de fondo --}}
            <ellipse cx="185" cy="268" rx="118" ry="17" fill="#E3D7FB"/>
            <ellipse cx="185" cy="150" rx="172" ry="66" fill="none" stroke="#B99AEF" stroke-width="2.2" transform="rotate(-14 185 150)" stroke-dasharray="560 70"/>

            <g class="flota">
                <g transform="rotate(-7 187 128)">
                    {{-- Cuerpo del calendario --}}
                    <rect x="112" y="44" width="150" height="168" rx="24" fill="url(#gCuerpo)"/>
                    <rect x="112" y="44" width="150" height="46" fill="url(#gBanda)" clip-path="url(#cCuerpo)"/>
                    <rect x="112" y="44" width="150" height="168" rx="24" fill="none" stroke="#6A4DB0" stroke-width="3"/>
                    {{-- Argollas --}}
                    <rect x="146" y="24" width="15" height="38" rx="7.5" fill="#EDE5FF" stroke="#6A4DB0" stroke-width="3"/>
                    <rect x="213" y="24" width="15" height="38" rx="7.5" fill="#EDE5FF" stroke="#6A4DB0" stroke-width="3"/>
                    {{-- Cara triste --}}
                    <path d="M150 104 l16 -5 M224 104 l-16 -5" stroke="#5B3FA8" stroke-width="3" stroke-linecap="round"/>
                    <path d="M150 114 q8 8 16 0 M208 114 q8 8 16 0" fill="none" stroke="#35265E" stroke-width="3.2" stroke-linecap="round"/>
                    <ellipse cx="146" cy="132" rx="9" ry="5.5" fill="#F5B9CF" opacity=".75"/>
                    <ellipse cx="228" cy="132" rx="9" ry="5.5" fill="#F5B9CF" opacity=".75"/>
                    <path d="M180 138 q7 -6 14 0" fill="none" stroke="#35265E" stroke-width="3" stroke-linecap="round"/>
                </g>

                {{-- Letrero con el código y las manitos --}}
                <g transform="rotate(-9 188 205)">
                    <rect x="96" y="160" width="184" height="96" rx="15" fill="#FFFFFF" stroke="#6A4DB0" stroke-width="3"/>
                    <path d="M104 172 h168" stroke="#EDE5FF" stroke-width="2"/>
                    <text x="188" y="229" text-anchor="middle" font-family="Figtree, sans-serif" font-weight="700" font-size="60" fill="url(#gNum)" letter-spacing="1">{{ $codigo }}</text>
                    <circle cx="100" cy="176" r="14" fill="#F4EFFF" stroke="#6A4DB0" stroke-width="3"/>
                    <circle cx="276" cy="176" r="14" fill="#F4EFFF" stroke="#6A4DB0" stroke-width="3"/>
                </g>
                <path transform="translate(246 168) scale(.85)" fill="#B99AEF" d="M12 0c.6 6.4 5.6 11.4 12 12-6.4.6-11.4 5.6-12 12-.6-6.4-5.6-11.4-12-12C6.4 11.4 11.4 6.4 12 0z"/>
            </g>

            {{-- Signo de pregunta y remolino de confusión --}}
            <text x="296" y="66" font-family="Marcellus, serif" font-size="58" fill="#8061C5">?</text>
            <path d="M96 52 c-10 -2 -14 -14 -4 -18 c10 -4 20 6 12 14 c-6 6 -16 0 -12 -6" fill="none" stroke="#8061C5" stroke-width="2.6" stroke-linecap="round" transform="translate(-4 4)"/>
            <path d="M84 66 c10 4 22 2 28 -6" fill="none" stroke="#8061C5" stroke-width="2.6" stroke-linecap="round"/>

            {{-- Destellos --}}
            <g transform="translate(58 98)" fill="#8061C5"><path d="M12 0c.6 6.4 5.6 11.4 12 12-6.4.6-11.4 5.6-12 12-.6-6.4-5.6-11.4-12-12C6.4 11.4 11.4 6.4 12 0z" transform="scale(1.1)"/></g>
            <g transform="translate(318 150)" fill="#8061C5"><path d="M12 0c.6 6.4 5.6 11.4 12 12-6.4.6-11.4 5.6-12 12-.6-6.4-5.6-11.4-12-12C6.4 11.4 11.4 6.4 12 0z"/></g>
            <circle cx="92" cy="122" r="2.5" fill="#B99AEF"/>
            <circle cx="312" cy="138" r="2" fill="#B99AEF"/>
        </svg>
        @endisset

        <h1>{{ $titulo }}</h1>
        <p>{{ $mensaje }}</p>
        <a href="{{ $inicio }}" class="btn btn-primary btn-error"><i class="bi bi-house"></i> Volver al inicio <i class="bi bi-arrow-right"></i></a>

        <div class="separador-destello">{!! $chispa !!}</div>
        <a href="{{ url('/') }}" class="enlace-explorar">o conocer más de Reservery <i class="bi bi-arrow-right"></i></a>
    </main>

    {{-- Fondo decorativo --}}
    <div class="nube izq" aria-hidden="true"></div>
    <div class="nube der" aria-hidden="true"></div>
    <div class="arco a1" aria-hidden="true"></div>
    <div class="arco a2" aria-hidden="true"></div>
    <span class="chispa-fondo" style="width:30px; left:11%; bottom:24%" aria-hidden="true">{!! $chispa !!}</span>
    <span class="chispa-fondo" style="width:20px; right:13%; bottom:26%" aria-hidden="true">{!! $chispa !!}</span>
</div>
@endsection
