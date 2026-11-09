@extends('layouts.app')

@section('titulo', 'No disponible')

@section('contenido')
<div class="alert alert-danger">
    <div class="fw-semibold mb-1"><i class="bi bi-x-circle"></i> No pudimos reservar {{ $pedidos->pluck('nombre')->join(' + ') }} el {{ \Illuminate\Support\Carbon::parse($datos['fecha'])->format('d/m/Y') }} de {{ $datos['hora_inicio'] }} a {{ $datos['hora_fin'] }}.</div>
    <ul class="mb-0 small">@foreach (collect($problemas)->pluck('detalle')->unique() as $p) <li>{{ $p }}</li> @endforeach</ul>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <h2 class="h5 fw-bold">Alternativas equivalentes</h2>
        <p class="text-muted small">Mismo tipo y categoría, capacidad suficiente y lo más cerca posible. En reservas compuestas cada opción incluye el paquete completo libre. <span class="text-nowrap">(Búsqueda: {{ $milisegundos }} ms)</span></p>

        @forelse ($alternativas as $alt)
            <div class="alternativa mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start">
                    <div>
                        @foreach ($alt['items'] as $item)
                            <div class="mb-1">
                                <span class="fw-semibold">{{ $item['nombre'] }}</span>
                                <small class="text-muted">· {{ $item['categoria'] }}{{ $item['capacidad'] ? ' · '.$item['capacidad'].' pers.' : '' }}{{ $item['ubicacion'] ? ' · '.$item['ubicacion'] : '' }}</small>
                                @if ($item['reemplaza']) <span class="badge text-bg-warning">en lugar de {{ $item['reemplaza'] }}</span> @endif
                            </div>
                        @endforeach
                    </div>
                    {{-- RF20: el usuario elige la alternativa; se envía la misma solicitud con los nuevos objetos --}}
                    <form method="POST" action="{{ route('reservas.store') }}">
                        @csrf
                        @foreach (['proposito_id', 'fecha', 'hora_inicio', 'hora_fin', 'motivo'] as $campo)
                            <input type="hidden" name="{{ $campo }}" value="{{ $datos[$campo] }}">
                        @endforeach
                        @foreach ($alt['objetos'] as $id) <input type="hidden" name="objetos[]" value="{{ $id }}"> @endforeach
                        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Reservar esta opción</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="card card-body text-muted">No encontramos alternativas equivalentes libres para ese horario.</div>
        @endforelse
    </div>

    <div class="col-lg-4">
        @if (Route::has('lista-espera.store'))
        <div class="card">
            <div class="card-header">Lista de espera</div>
            <div class="card-body">
                <p class="small">Si prefieres exactamente lo que pediste, anótate. Te avisaremos por orden de llegada si se libera.</p>
                <form method="POST" action="{{ route('lista-espera.store') }}">
                    @csrf
                    @foreach (['proposito_id', 'fecha', 'hora_inicio', 'hora_fin', 'motivo'] as $campo)
                        <input type="hidden" name="{{ $campo }}" value="{{ $datos[$campo] }}">
                    @endforeach
                    @foreach ($datos['objetos'] as $id) <input type="hidden" name="objetos[]" value="{{ $id }}"> @endforeach
                    <button class="btn btn-outline-primary w-100"><i class="bi bi-hourglass-split"></i> Anotarme en la lista de espera</button>
                </form>
            </div>
        </div>
        @endif
        <a href="{{ route('reservas.create', ['fecha' => $datos['fecha']]) }}" class="btn btn-link mt-2">Cambiar fecha u horario</a>
    </div>
</div>
@endsection
