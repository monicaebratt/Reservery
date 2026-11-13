<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de ocupación</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #35265E; }
        .cabecera { border-bottom: 3px solid #8061C5; padding-bottom: 8px; margin-bottom: 14px; }
        .marca { font-size: 18px; font-weight: bold; color: #8061C5; }
        h1 { font-size: 15px; margin: 4px 0 0; }
        .meta { color: #6b6880; margin-top: 4px; }
        .kpis td { border: none; padding: 0 18px 10px 0; }
        .kpi { font-size: 18px; font-weight: bold; }
        table.datos { width: 100%; border-collapse: collapse; }
        .datos th { background: #8061C5; color: #fff; text-align: left; padding: 6px; }
        .datos td { padding: 5px 6px; border-bottom: 1px solid #e6e3dc; }
        .datos tr:nth-child(even) td { background: #f6f5f2; }
        .num { text-align: right; }
        .barra { background: #e6e3dc; height: 7px; width: 110px; display: inline-block; vertical-align: middle; }
        .barra span { background: #f59e0b; height: 7px; display: block; }
        .bajo td { color: #b91c1c; }
        .etiqueta { background: #fee2e2; color: #b91c1c; padding: 1px 5px; border-radius: 3px; font-size: 9px; }
        .pie { margin-top: 14px; color: #6b6880; font-size: 9.5px; }
    </style>
</head>
<body>
    <div class="cabecera">
        <div class="marca">Reservery</div>
        <h1>Reporte de ocupación · {{ $organizacion }}</h1>
        <div class="meta">Periodo: {{ $reporte->periodo() }} · Generado por {{ $generadoPor }} el {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <table class="kpis">
        <tr>
            <td><div class="kpi">{{ $reporte->tasaOcupacion() }}%</div>Tasa de ocupación global</td>
            <td><div class="kpi">{{ $reporte->filas->sum('solicitudes') }}</div>Solicitudes en el periodo</td>
            <td><div class="kpi">{{ $reporte->filas->sum('horas_reservadas') }} h</div>Horas reservadas</td>
            <td><div class="kpi">{{ $reporte->subutilizados()->count() }}</div>Subutilizados (&lt; {{ $reporte->umbralSubutilizacion }}%)</td>
        </tr>
    </table>

    <table class="datos">
        <thead>
            <tr>
                <th>Espacio / recurso</th><th>Tipo</th><th>Categoría</th>
                <th class="num">Solicitudes</th><th class="num">Aprobadas</th><th class="num">Canceladas / rech.</th>
                <th class="num">Horas operativas</th><th class="num">Horas reservadas</th><th>Tasa de ocupación</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reporte->filas as $f)
                <tr class="{{ $f['subutilizado'] ? 'bajo' : '' }}">
                    <td>{{ $f['nombre'] }} @if ($f['subutilizado']) <span class="etiqueta">Subutilizado</span> @endif</td>
                    <td>{{ ucfirst($f['tipo']) }}</td>
                    <td>{{ $f['categoria'] }}</td>
                    <td class="num">{{ $f['solicitudes'] }}</td>
                    <td class="num">{{ $f['aprobadas'] }}</td>
                    <td class="num">{{ $f['canceladas'] }}</td>
                    <td class="num">{{ $f['horas_operativas'] }}</td>
                    <td class="num">{{ $f['horas_reservadas'] }}</td>
                    <td><span class="barra"><span style="width: {{ min($f['tasa'], 100) }}%"></span></span> {{ $f['tasa'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="pie">
        La tasa compara las horas de reservas aprobadas o completadas con las horas operativas de cada objeto
        (su horario, sin festivos si la organización cierra esos días y sin las horas bloqueadas).
        Se marca como subutilizado lo que queda por debajo del umbral configurado por la organización.
    </p>
</body>
</html>
