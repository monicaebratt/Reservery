@extends('layouts.app')

@section('titulo', 'Analítica')
@section('subtitulo', 'Cómo se usan tus espacios y recursos · '.$reporte->periodo())
@section('acciones')
    <a href="{{ route('analitica.pdf', array_filter(['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString(), 'tipo' => $tipo])) }}" class="btn btn-light d-none d-md-inline-flex align-items-center gap-1"><i class="bi bi-file-earmark-pdf"></i> Reporte PDF</a>
@endsection

@section('contenido')
@php
    $q = array_filter(['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString(), 'tipo' => $tipo]);
    $maxDemanda = max(1, collect($demanda['matriz'])->flatten()->max());
@endphp

{{-- Filtros compactos --}}
<form class="d-flex flex-wrap gap-2 align-items-center mb-4" method="GET">
    <label class="filtro-pastilla"><i class="bi bi-calendar3"></i>
        <input type="date" name="desde" value="{{ $desde->toDateString() }}" aria-label="Desde"> <span class="text-muted">a</span>
        <input type="date" name="hasta" value="{{ $hasta->toDateString() }}" aria-label="Hasta">
    </label>
    <label class="filtro-pastilla"><i class="bi bi-grid"></i>
        <select name="tipo" aria-label="Tipo"><option value="">Espacios y recursos</option>
            <option value="espacio" @selected($tipo === 'espacio')>Solo espacios</option><option value="recurso" @selected($tipo === 'recurso')>Solo recursos</option></select>
    </label>
    <label class="filtro-pastilla"><i class="bi bi-tag"></i>
        <select name="categoria" aria-label="Categoría"><option value="">Todas las categorías</option>
            @foreach ($categorias as $c) <option @selected($categoria === $c)>{{ $c }}</option> @endforeach</select>
    </label>
    <button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Aplicar</button>
</form>

<div class="row g-3 mb-4">
    @foreach ([
        ['bi-pie-chart', $reporte->tasaOcupacion().'%', 'Ocupación global'],
        ['bi-calendar-check', $demanda['total_solicitudes'], 'Solicitudes'],
        ['bi-clock', $reporte->filas->sum('horas_reservadas'), 'Horas reservadas'],
        ['bi-arrow-down-circle', $reporte->subutilizados()->count(), 'Subutilizados'],
    ] as [$icono, $valor, $etiqueta])
        <div class="col-6 col-xl-3">
            <div class="indicador">
                <span class="circulo"><i class="bi {{ $icono }}"></i></span>
                <div><div class="numero">{{ $valor }}</div><div class="etiqueta">{{ $etiqueta }}</div></div>
            </div>
        </div>
    @endforeach
</div>

{{-- Ranking: lo más usado --}}
<div class="row g-4 mb-4">
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header"><svg class="destello" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0c.6 6.4 5.6 11.4 12 12-6.4.6-11.4 5.6-12 12-.6-6.4-5.6-11.4-12-12C6.4 11.4 11.4 6.4 12 0z"/></svg> Lo más usado</div>
            <div class="card-body pt-1">
                @forelse ($reporte->filas->sortByDesc('tasa')->take(5)->values() as $i => $f)
                    <div class="ranking-fila" style="grid-template-columns: 26px 1fr auto">
                        <span class="pos">{{ $i + 1 }}</span>
                        <div>
                            <div class="fw-semibold small">{{ $f['nombre'] }}</div>
                            <div class="barra-progreso"><span style="width: {{ min(100, $f['tasa']) }}%"></span></div>
                        </div>
                        <span class="fw-semibold small">{{ $f['tasa'] }}%</span>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Sin datos en este periodo.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-header">Solicitudes por estado</div>
            <div class="card-body"><div style="height:220px"><canvas id="graficoEstados" aria-label="Solicitudes por estado"></canvas></div></div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card h-100">
            <div class="card-header">Subutilizados</div>
            <div class="filas">
                @forelse ($reporte->subutilizados() as $s)
                    <a href="{{ route('objetos.show', $s['id']) }}" class="fila-suave py-2"><span class="principal small fw-semibold">{{ $s['nombre'] }}</span><span class="estado estado-rechazada">{{ $s['tasa'] }}%</span></a>
                @empty
                    <p class="small text-muted mb-0">Ninguno en este periodo.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- + --}}
    <div class="col-12">
        <div class="card h-100">
            <div class="card-header">Ocupación por espacio o recurso <small class="text-muted fw-normal">· en gris, los que están por debajo de {{ $reporte->umbralSubutilizacion }}%</small></div>
            <div class="card-body"><div style="height: {{ max(260, $reporte->filas->count() * 26) }}px"><canvas id="graficoOcupacion" aria-label="Ocupación por objeto"></canvas></div></div>
        </div>
    </div>
</div>

{{-- Horarios y días de mayor demanda --}}
<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">Mapa de demanda: solicitudes por día y hora{{ $tipo ? ' · '.($tipo === 'espacio' ? 'espacios' : 'recursos') : '' }}{{ $categoria ? ' · '.$categoria : '' }}</div>
            <div class="card-body table-responsive">
                <table class="table table-sm calor mb-0">
                    <thead><tr><th></th>@foreach ($demanda['horas'] as $h)<th>{{ substr($h, 0, 2) }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($demanda['matriz'] as $dia => $fila)
                            <tr>
                                <th class="text-start">{{ mb_substr($demanda['dias'][$dia], 0, 3) }}</th>
                                @foreach ($fila as $hora => $total)
                                    @php($a = $total / $maxDemanda)
                                    <td style="background: rgba(128,97,197,{{ round(0.06 + $a * 0.84, 2) }}); color: {{ $a > .5 ? '#fff' : '#35265E' }}"
                                        title="{{ $demanda['dias'][$dia] }} {{ sprintf('%02d:00', $hora) }}: {{ $total }} solicitudes">{{ $total ?: '' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">Picos de demanda</div>
            <ol class="list-group list-group-flush list-group-numbered">
                @forelse ($demanda['picos'] as $p)
                    <li class="list-group-item d-flex justify-content-between"><span class="ms-2 me-auto">{{ $p['dia_nombre'] }} · {{ $p['hora'] }}</span><span class="estado estado-completada">{{ $p['total'] }}</span></li>
                @empty
                    <li class="list-group-item small text-muted">Sin solicitudes en este periodo.</li>
                @endforelse
            </ol>
            <div class="card-body pt-2"><div style="height:160px"><canvas id="graficoHoras" aria-label="Solicitudes por hora"></canvas></div></div>
        </div>
    </div>
</div>

{{-- Vista de tabla del reporte (accesible y exportable en PDF) --}}
<div class="card">
    <div class="card-header">Detalle del reporte de ocupación · {{ $reporte->periodo() }}</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Objeto</th><th>Categoría</th><th class="text-end">Solicitudes</th><th class="text-end">Aprobadas</th><th class="text-end">Horas operativas</th><th class="text-end">Horas reservadas</th><th class="text-end">Ocupación</th></tr></thead>
            <tbody>
                @foreach ($reporte->filas as $f)
                    <tr class="{{ $f['subutilizado'] ? 'table-danger' : '' }}">
                        <td>{{ $f['nombre'] }} @if ($f['subutilizado']) <span class="badge text-bg-danger">Subutilizado</span> @endif</td>
                        <td>{{ $f['categoria'] }}</td>
                        <td class="text-end">{{ $f['solicitudes'] }}</td>
                        <td class="text-end">{{ $f['aprobadas'] }}</td>
                        <td class="text-end">{{ $f['horas_operativas'] }}</td>
                        <td class="text-end">{{ $f['horas_reservadas'] }}</td>
                        <td class="text-end fw-semibold">{{ $f['tasa'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.js"></script>
<script>
    Chart.defaults.font.family = "'Figtree', sans-serif";
    Chart.defaults.color = '#6E6590';
    Chart.defaults.borderColor = '#EEE8FB';

    const filas = @json($reporte->filas->values());
    const umbral = {{ $reporte->umbralSubutilizacion }};
    const estados = @json($resumen['por_estado']);
    const porHora = @json($demanda['por_hora']);

    new Chart(document.getElementById('graficoOcupacion'), {
        type: 'bar',
        data: {
            labels: filas.map(f => f.nombre),
            datasets: [{
                label: 'Ocupación (%)',
                data: filas.map(f => f.tasa),
                backgroundColor: filas.map(f => f.subutilizado ? '#D9D2EA' : '#8061C5'),
                borderRadius: 4, barThickness: 14,
            }],
        },
        options: {
            indexAxis: 'y', maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => ` ${c.raw}% de sus horas operativas` + (filas[c.dataIndex].subutilizado ? ' · subutilizado' : '') } } },
            scales: { x: { min: 0, max: 100, ticks: { callback: v => v + '%' } }, y: { grid: { display: false } } },
        },
    });

    new Chart(document.getElementById('graficoEstados'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(estados).map(e => e.charAt(0).toUpperCase() + e.slice(1)),
            datasets: [{ data: Object.values(estados), backgroundColor: ['#E0A458', '#6FB59A', '#D9718C', '#CFC8DF', '#8061C5'], borderWidth: 2, borderColor: '#fff' }],
        },
        options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'right', labels: { boxWidth: 10 } } } },
    });

    new Chart(document.getElementById('graficoHoras'), {
        type: 'bar',
        data: { labels: Object.keys(porHora).map(h => h.slice(0, 2)), datasets: [{ label: 'Solicitudes', data: Object.values(porHora), backgroundColor: '#8061C5', borderRadius: 3 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } },
    });
</script>
@endpush
