{{-- Maestra de la zona privada. Hereda de la maestra base. --}}
@extends('layouts.base')

@section('cuerpo')
@php
    $usuario = auth()->user();
    $org = $usuario->organizacion;
    $activa = $usuario->tieneOrganizacionActiva();
    $item = fn ($ruta, $icono, $texto, $patron = null) => Route::has($ruta)
        ? '<a href="'.route($ruta).'" class="'.(request()->routeIs($patron ?? $ruta) ? 'activo' : '').'"><i class="bi '.$icono.'"></i> '.e($texto).'</a>'
        : '';
@endphp
@php($destello = '<svg class="destello" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0c.6 6.4 5.6 11.4 12 12-6.4.6-11.4 5.6-12 12-.6-6.4-5.6-11.4-12-12C6.4 11.4 11.4 6.4 12 0z"/></svg>')
<div class="app-wrap">
    <aside class="sidebar offcanvas-lg offcanvas-start" id="menuLateral" tabindex="-1" aria-label="Menú principal">
        <div class="sidebar-top">
            <a href="{{ route('dashboard') }}" class="logo-lateral" aria-label="Reservery, inicio">
                <img src="{{ asset('img/logo-icono.png') }}" alt="" class="icono">
                <img src="{{ asset('img/logo-texto.png') }}" alt="Reservery" class="texto">
            </a>
            <button class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#menuLateral" aria-label="Cerrar menú"></button>
        </div>

        <nav class="menu">
            @if ($activa)
                {!! $item('dashboard', 'bi-house', 'Inicio') !!}
                {!! $item('disponibilidad.index', 'bi-calendar-week', 'Disponibilidad') !!}
                @unless ($usuario->esAdmin())
                    {!! $item('reservas.create', 'bi-plus-circle', 'Nueva reserva') !!}
                @endunless
                {!! $item('reservas.index', 'bi-calendar-check', $usuario->esAdmin() ? 'Solicitudes' : 'Mis reservas', 'reservas.index') !!}
                {!! $item('lista-espera.index', 'bi-hourglass-split', 'Lista de espera', 'lista-espera.*') !!}
                {!! $item('reemplazos.index', 'bi-arrow-left-right', 'Reemplazos', 'reemplazos.*') !!}
                {!! $item('objetos.index', 'bi-door-open', 'Espacios y recursos', 'objetos.*') !!}

                @if ($usuario->esAdmin())
                    <span class="menu-titulo">Administración</span>
                    {!! $item('calendario.index', 'bi-calendar3', 'Calendario') !!}
                    {!! $item('bloqueos.index', 'bi-slash-circle', 'Bloqueos') !!}
                    {!! $item('propositos.index', 'bi-sort-down', 'Propósitos y prioridad') !!}
                    {!! $item('analitica.index', 'bi-bar-chart', 'Analítica') !!}
                    {!! $item('listados.reservas', 'bi-table', 'Listado de reservas') !!}
                    {!! $item('listados.objetos', 'bi-list-ul', 'Listado de inventario') !!}
                @endif
            @endif

            @if ($usuario->esOrganizacion())
                <span class="menu-titulo">Organización</span>
                {!! $item('organizacion.edit', 'bi-sliders', 'Configuración') !!}
                {!! $item('miembros.index', 'bi-people', 'Miembros y roles') !!}
            @endif

            @if ($activa && config('reservery.consola_api'))
                <span class="menu-titulo">Desarrollo</span>
                {!! $item('api.consola', 'bi-plug', 'API y tokens') !!}
            @endif
        </nav>

        <div class="sidebar-adorno" aria-hidden="true">{!! $destello !!}</div>
        <div class="tarjeta-usuario">
            <a href="{{ Route::has('perfil.edit') && $activa ? route('perfil.edit') : '#' }}" class="usuario-activo text-decoration-none text-reset mb-2">
                <img src="{{ $usuario->foto_url }}" alt="">
                <div class="lh-sm text-truncate">
                    <div class="fw-semibold small text-truncate">{{ $usuario->name }}</div>
                    <small class="text-muted text-truncate d-block">{{ $usuario->rol_nombre }}{{ $org ? ' · '.$org->nombre : '' }}</small>
                </div>
            </a>
            <form action="{{ route('logout') }}" method="POST" class="menu">
                @csrf
                <button type="submit"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</button>
            </form>
        </div>
    </aside>

    <div class="contenido">
        <header class="cabecera">
            <button class="btn-icono d-lg-none me-2" data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-label="Abrir menú"><i class="bi bi-list"></i></button>
            <div class="titulos">
                <div class="titulo-con-destello">{!! $destello !!}<h1 class="titulo-pagina mb-0">@yield('titulo')</h1></div>
                @hasSection('subtitulo')<p class="subtitulo">@yield('subtitulo')</p>@endif
            </div>

            <div class="cabecera-acciones">
                @yield('acciones')
                @if ($activa && Route::has('notificaciones.contador'))
                    {{-- Campana: se actualiza sola cada 20 segundos (RF11) --}}
                    <div class="dropdown">
                        <button class="btn-icono position-relative" data-bs-toggle="dropdown" aria-label="Notificaciones">
                            <i class="bi bi-bell"></i>
                            <span id="contadorNotif" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0 campana">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2">
                                <strong class="small">Notificaciones</strong>
                                <a href="{{ route('notificaciones.index') }}" class="small">Ver todas</a>
                            </div>
                            <div id="listaNotif"><div class="px-3 py-3 small text-muted">Cargando…</div></div>
                        </div>
                    </div>
                @endif
                <a href="{{ Route::has('perfil.edit') && $activa ? route('perfil.edit') : '#' }}" class="d-none d-sm-block" aria-label="Mi perfil">
                    <img src="{{ $usuario->foto_url }}" alt="" class="avatar-cabecera">
                </a>
            </div>
        </header>

        <main class="cuerpo">
            @include('partials.alertas')
            @if ($usuario->esOrganizacion() && ! $activa && ! request()->routeIs('organizacion.edit'))
                {{-- RF01.1: hasta habilitar el acceso solo se ve la configuración --}}
                <div class="tarjeta-marca d-flex flex-wrap align-items-center gap-3 mb-4" style="min-height:0">
                    <div class="me-auto">
                        <div class="fw-bold">Tu organización aún no está habilitada</div>
                        <div class="small text-muted">Completa los datos básicos y habilita el acceso para ver el panel de administración e invitar a tu gente.</div>
                    </div>
                    <a href="{{ route('organizacion.edit') }}#habilitar" class="btn btn-primary btn-sm">Ir a habilitar</a>
                </div>
            @endif
            @yield('contenido')
        </main>
    </div>
</div>
@endsection

@if (auth()->user()->tieneOrganizacionActiva() && Route::has('notificaciones.contador'))
@push('scripts')
<script>
    (function () {
        const contador = document.getElementById('contadorNotif');
        const lista = document.getElementById('listaNotif');
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        async function actualizar() {
            try {
                const r = await fetch('{{ route('notificaciones.contador') }}', { headers: { Accept: 'application/json' } });
                const d = await r.json();
                contador.textContent = d.sin_leer;
                contador.classList.toggle('d-none', d.sin_leer === 0);
                lista.innerHTML = d.ultimas.length ? d.ultimas.map(n => `
                    <form method="POST" action="{{ url('notificaciones') }}/${n.id}/leer">
                        <input type="hidden" name="_token" value="${csrf}"><input type="hidden" name="_method" value="PATCH">
                        <button class="dropdown-item px-3 py-2 ${n.leida ? 'text-muted' : ''}">
                            <div class="small fw-semibold">${n.leida ? '' : '<i class="bi bi-circle-fill text-primary me-1" style="font-size:.5rem"></i>'}${escapar(n.titulo)}</div>
                            <div class="small text-wrap">${escapar(n.mensaje)}</div>
                            <div class="small text-muted">${n.hace}</div>
                        </button>
                    </form>`).join('') : '<div class="px-3 py-3 small text-muted">Sin notificaciones.</div>';
            } catch (e) { /* sin conexión: se intenta de nuevo luego */ }
        }
        const escapar = (t) => String(t).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        actualizar();
        setInterval(actualizar, 20000);
    })();
</script>
@endpush
@endif
