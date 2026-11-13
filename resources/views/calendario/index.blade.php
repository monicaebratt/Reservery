@extends('layouts.app')

@section('titulo', 'Calendario centralizado')

@section('contenido')
<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label small mb-1" for="filtroObjeto">Espacio o recurso</label>
            <select id="filtroObjeto" class="form-select">
                <option value="">Todos</option>
                @foreach ($objetos as $o) <option value="{{ $o->id }}">{{ $o->nombre }}</option> @endforeach
            </select>
        </div>
        <div>
            <label class="form-label small mb-1" for="filtroTipo">Tipo</label>
            <select id="filtroTipo" class="form-select"><option value="">Todos</option><option value="espacio">Espacios</option><option value="recurso">Recursos</option></select>
        </div>
        <div class="form-check ms-2 mb-2">
            <input class="form-check-input" type="checkbox" id="filtroFinalizadas">
            <label class="form-check-label small" for="filtroFinalizadas">Incluir completadas</label>
        </div>
        <div class="ms-auto small text-muted d-flex flex-wrap gap-3 mb-2">
            <span>El color es el del propósito</span>
            <span><span class="leyenda" style="background: var(--rv-profundo)"></span>Bloqueo</span>
            <span><span class="leyenda" style="background: repeating-linear-gradient(135deg, var(--rv-lila) 0 3px, #fff 3px 6px)"></span>Por aprobar</span>
        </div>
    </div>
</div>
<div class="card"><div class="card-body"><div id="calendario"></div></div></div>
@endsection

@push('estilos')
<style>
    .fc .estado-pendiente { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,.35) 0 4px, transparent 4px 8px); }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/es.global.min.js"></script>
<script>
    // Todas las reservas activas y los bloqueos en una sola vista
    document.addEventListener('DOMContentLoaded', () => {
        const filtros = () => ({
            objeto: document.getElementById('filtroObjeto').value,
            tipo: document.getElementById('filtroTipo').value,
            finalizadas: document.getElementById('filtroFinalizadas').checked ? 1 : 0,
        });
        const calendario = new FullCalendar.Calendar(document.getElementById('calendario'), {
            locale: 'es',
            initialView: window.innerWidth < 768 ? 'listWeek' : 'timeGridWeek',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
            slotMinTime: '06:00:00', slotMaxTime: '22:00:00', allDaySlot: false, nowIndicator: true, height: 'auto',
            events: { url: '{{ route('calendario.eventos') }}', extraParams: filtros },
            eventDidMount: (info) => {
                const p = info.event.extendedProps;
                info.el.title = p.tipo === 'bloqueo' ? info.event.title : `${info.event.title}\n${p.proposito} · ${p.estado} · ${p.solicitante}`;
            },
        });
        calendario.render();
        ['filtroObjeto', 'filtroTipo', 'filtroFinalizadas'].forEach(id => document.getElementById(id).addEventListener('change', () => calendario.refetchEvents()));
    });
</script>
@endpush
