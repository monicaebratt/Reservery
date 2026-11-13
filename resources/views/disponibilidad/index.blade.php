@extends('layouts.app')

@section('titulo', 'Disponibilidad')
@section('subtitulo', auth()->user()->esAdmin() ? 'Así está la agenda de tus espacios y recursos.' : 'Elige un espacio libre y resérvalo en segundos.')

@section('contenido')
@php($puedeReservar = ! auth()->user()->esAdmin())

{{-- Controles: vista, semana/día y filtros --}}
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <div class="segmentado" role="tablist" aria-label="Vista">
        <button type="button" data-vista="semana" class="activo"><i class="bi bi-calendar-week"></i> Semana</button>
        <button type="button" data-vista="dia"><i class="bi bi-calendar-day"></i> Día</button>
    </div>

    <div class="filtro-pastilla">
        <button type="button" class="btn btn-link p-0" id="anterior" aria-label="Anterior"><i class="bi bi-chevron-left"></i></button>
        <span id="rango" class="fw-semibold px-1 text-nowrap">—</span>
        <button type="button" class="btn btn-link p-0" id="siguiente" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></button>
        <span class="position-relative">
            <button type="button" class="btn btn-link p-0 ms-1" onclick="try { document.getElementById('fecha').showPicker() } catch (e) { document.getElementById('fecha').focus() }" aria-label="Elegir fecha"><i class="bi bi-calendar3"></i></button>
            <input type="date" id="fecha" value="{{ $fecha }}" aria-label="Ir a una fecha" class="position-absolute start-0 top-0 opacity-0" style="width:1px;height:1px">
        </span>
    </div>
    <button type="button" class="btn btn-light btn-sm" id="hoy">Hoy</button>

    <div class="ms-lg-auto d-flex flex-wrap gap-2">
        <label class="filtro-pastilla"><i class="bi bi-grid"></i>
            <select id="tipo" aria-label="Tipo"><option value="espacio">Espacios</option><option value="recurso">Recursos</option><option value="">Todos</option></select>
        </label>
        <label class="filtro-pastilla"><i class="bi bi-tag"></i>
            <select id="categoria" aria-label="Categoría"><option value="">Todas las categorías</option>@foreach ($categorias as $c)<option>{{ $c }}</option>@endforeach</select>
        </label>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div id="vistaSemana" class="table-responsive"><p class="text-muted text-center py-5 mb-0">Cargando disponibilidad…</p></div>
        <div id="vistaDia" class="p-3 d-none">
            <div class="agenda-fila d-none d-md-grid border-0 pb-0">
                <div></div>
                <div class="horas-eje">@foreach (range(6, 22, 2) as $h)<span>{{ sprintf('%02d', $h) }}:00</span>@endforeach</div>
            </div>
            <div id="agenda"></div>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-3 small text-muted px-4 py-3 border-top" style="border-color: var(--rv-linea) !important">
        <span><span class="punto" style="background:#6FB59A"></span>Libre{{ $puedeReservar ? ' (tócalo para reservar)' : '' }}</span>
        <span><span class="punto" style="background:var(--rv-violeta)"></span>Reservado</span>
        <span><span class="punto" style="background:#E0A458"></span>Por aprobar</span>
        <span><span class="punto" style="background:var(--rv-profundo)"></span>Bloqueado</span>
        <span class="ms-auto"><span class="spinner-grow spinner-grow-sm text-success" style="width:.5rem;height:.5rem"></span> En vivo · <span id="actualizado">—</span></span>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // La agenda se consulta al servidor cada 15 segundos sin recargar la página
    const INICIO = 6 * 60, FIN = 22 * 60;
    const PUEDE_RESERVAR = {{ $puedeReservar ? 'true' : 'false' }};
    const URL_CREAR = '{{ url('reservas/create') }}';
    const $ = (id) => document.getElementById(id);
    let vista = 'semana';
    const minutos = (h) => { const [a, b] = h.split(':').map(Number); return a * 60 + b; };
    const posicion = (ini, fin) => {
        const i = Math.max(minutos(ini), INICIO), f = Math.min(minutos(fin), FIN);
        return `left:${(i - INICIO) / (FIN - INICIO) * 100}%;width:${Math.max(f - i, 0) / (FIN - INICIO) * 100}%`;
    };
    const esc = (t) => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const enlaceCrear = (fecha, inicio, id) => `${URL_CREAR}?fecha=${fecha}&hora_inicio=${inicio}&objeto=${id}`;

    function parametros() {
        const p = new URLSearchParams({ fecha: $('fecha').value });
        if ($('tipo').value) p.append('tipo', $('tipo').value);
        if ($('categoria').value) p.append('categoria', $('categoria').value);
        return p;
    }

    async function cargar() {
        try {
            const ruta = vista === 'semana' ? '{{ route('disponibilidad.semana') }}' : '{{ route('disponibilidad.agenda') }}';
            const r = await fetch(`${ruta}?${parametros()}`, { headers: { Accept: 'application/json' } });
            const d = await r.json();
            $('actualizado').textContent = d.actualizado;
            vista === 'semana' ? pintarSemana(d) : pintarDia(d);
        } catch (e) {
            $('actualizado').textContent = 'sin conexión';
        }
    }

    // Semana: espacios × días
    function pintarSemana(d) {
        $('rango').textContent = d.rango;
        if (!d.objetos.length) { $('vistaSemana').innerHTML = '<p class="text-muted text-center py-5 mb-0">No hay espacios ni recursos con ese filtro.</p>'; return; }
        const cab = d.dias.map(x => `<th class="${x.hoy ? 'hoy' : ''}">${x.nombre} ${x.numero}<small>${x.mes}</small></th>`).join('');
        const filas = d.objetos.map(o => `<tr>
            <td class="espacio-col"><div class="d-flex gap-2 align-items-center">
                <img src="${o.imagen_url}" class="miniatura" alt="">
                <div class="lh-sm"><a href="{{ url('objetos') }}/${o.id}" class="fw-semibold text-decoration-none">${esc(o.nombre)}</a>
                <div class="small text-muted">${esc(o.categoria)}${o.capacidad ? ' · ' + o.capacidad + ' pers.' : ''}</div></div>
            </div></td>
            ${d.dias.map(x => `<td class="${x.hoy ? 'hoy' : ''}">${celda(o, x)}</td>`).join('')}
        </tr>`).join('');
        $('vistaSemana').innerHTML = `<table class="semana"><thead><tr><th class="text-start ps-4">Espacio</th>${cab}</tr></thead><tbody>${filas}</tbody></table>`;
    }

    function celda(o, dia) {
        const a = o.dias[dia.fecha];
        if (!a) return '';
        if (!a.disponible) return '<span class="ficha cerrado">Inhabilitado</span>';
        if (a.festivo) return `<span class="ficha cerrado" title="${esc(a.festivo)}">Festivo</span>`;
        if (!a.horario) return '<span class="ficha cerrado">Cerrado</span>';
        if (dia.pasado) return '<span class="ficha cerrado">—</span>';

        const items = [
            ...a.libre.filter(l => minutos(l.fin) - minutos(l.inicio) >= 30).map(l => ({ ...l, clase: 'libre' })),
            ...a.ocupado.map(x => ({ ...x, clase: x.tipo === 'bloqueo' ? 'bloqueado' : (x.estado === 'pendiente' ? 'pendiente' : 'reservada') })),
        ].sort((p, q) => p.inicio.localeCompare(q.inicio));
        if (!items.length) return '<span class="ficha cerrado">Sin cupos</span>';

        const etiqueta = { libre: 'Libre', reservada: 'Reservado', pendiente: 'Por aprobar', bloqueado: 'Bloqueado' };
        return items.slice(0, 4).map(i => {
            const contenido = `<strong>${etiqueta[i.clase]}</strong>${i.inicio}–${i.fin}`;
            const titulo = i.motivo ? esc(i.motivo) : (i.proposito ? esc(i.proposito) : '');
            return i.clase === 'libre' && PUEDE_RESERVAR
                ? `<a class="ficha libre" href="${enlaceCrear(dia.fecha, i.inicio, o.id)}" title="Reservar">${contenido}</a>`
                : `<span class="ficha ${i.clase}" title="${titulo}">${contenido}</span>`;
        }).join('') + (items.length > 4 ? `<span class="small text-muted d-block text-center">+${items.length - 4} más</span>` : '');
    }

    // Día: línea de tiempo
    function pintarDia(d) {
        const f = new Date(d.fecha + 'T00:00');
        $('rango').textContent = f.toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'short' });
        $('agenda').innerHTML = d.objetos.length ? d.objetos.map(fila).join('') : '<p class="text-muted text-center py-4 mb-0">No hay espacios ni recursos con ese filtro.</p>';
    }

    function fila(o) {
        let linea;
        if (!o.disponible) linea = '<div class="linea cerrado">Inhabilitado</div>';
        else if (o.festivo) linea = `<div class="linea cerrado">Festivo: ${esc(o.festivo)}</div>`;
        else if (!o.horario) linea = '<div class="linea cerrado">No abre este día</div>';
        else {
            const libres = o.libre.map(l => PUEDE_RESERVAR
                ? `<a class="franja libre" style="${posicion(l.inicio, l.fin)}" title="Libre ${l.inicio}–${l.fin}" href="${enlaceCrear($('fecha').value, l.inicio, o.id)}">${l.inicio}–${l.fin}</a>`
                : `<div class="franja libre" style="${posicion(l.inicio, l.fin)}" title="Libre ${l.inicio}–${l.fin}">${l.inicio}–${l.fin}</div>`).join('');
            const ocupados = o.ocupado.map(x => x.tipo === 'bloqueo'
                ? `<div class="franja bloqueo" style="${posicion(x.inicio, x.fin)}" title="Bloqueado: ${esc(x.motivo)}">${esc(x.motivo)}</div>`
                : `<div class="franja reserva ${x.estado}" style="${posicion(x.inicio, x.fin)}" title="${x.inicio}–${x.fin} · ${esc(x.proposito)} · ${x.estado}${x.solicitante ? ' · ' + esc(x.solicitante) : ''}">${x.inicio}–${x.fin} ${esc(x.proposito)}</div>`).join('');
            linea = `<div class="linea">${libres}${ocupados}</div>`;
        }
        return `<div class="agenda-fila">
            <div class="d-flex gap-2 align-items-center">
                <img src="${o.imagen_url}" class="miniatura" alt="">
                <div class="lh-sm"><a href="{{ url('objetos') }}/${o.id}" class="fw-semibold text-decoration-none">${esc(o.nombre)}</a>
                <div class="small text-muted">${esc(o.categoria)}${o.horario ? ' · ' + o.horario.inicio + '–' + o.horario.fin : ''}</div></div>
            </div>${linea}</div>`;
    }

    // Controles
    function mover(dias) {
        const f = new Date($('fecha').value + 'T00:00');
        f.setDate(f.getDate() + dias);
        $('fecha').value = f.toISOString().slice(0, 10);
        cargar();
    }
    $('anterior').addEventListener('click', () => mover(vista === 'semana' ? -7 : -1));
    $('siguiente').addEventListener('click', () => mover(vista === 'semana' ? 7 : 1));
    $('hoy').addEventListener('click', () => { $('fecha').value = '{{ today()->toDateString() }}'; cargar(); });
    document.querySelectorAll('[data-vista]').forEach(b => b.addEventListener('click', () => {
        vista = b.dataset.vista;
        document.querySelectorAll('[data-vista]').forEach(x => x.classList.toggle('activo', x === b));
        $('vistaSemana').classList.toggle('d-none', vista !== 'semana');
        $('vistaDia').classList.toggle('d-none', vista !== 'dia');
        cargar();
    }));
    ['fecha', 'tipo', 'categoria'].forEach(id => $(id).addEventListener('change', cargar));
    cargar();
    setInterval(cargar, 15000);
</script>
@endpush
