@extends('layouts.app')

@section('titulo', 'Bloqueos')

@section('contenido')
<div class="row g-4">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">Inhabilitar un horario</div>
            <div class="card-body">
                @if (session('afectadas'))
                    <div class="alert alert-warning small">
                        <strong>Hay reservas activas en ese rango:</strong>
                        <ul class="mb-2">@foreach (session('afectadas') as $a) <li>{{ $a }}</li> @endforeach</ul>
                        Elige qué hacer con ellas y vuelve a guardar.
                    </div>
                @endif
                <form method="POST" action="{{ route('bloqueos.store') }}" novalidate>
                    @csrf
                    <label class="form-label" for="objetos">Espacios o recursos</label>
                    <select id="objetos" name="objetos[]" multiple size="7" class="form-select @error('objetos') is-invalid @enderror">
                        @foreach ($objetos->groupBy('tipo') as $tipo => $grupo)
                            <optgroup label="{{ $tipo === 'espacio' ? 'Espacios' : 'Recursos' }}">
                                @foreach ($grupo as $o) <option value="{{ $o->id }}" @selected(in_array($o->id, old('objetos', [])))>{{ $o->nombre }}</option> @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <div class="form-text">Mantén Ctrl (o Cmd) para elegir varios.</div>
                    @error('objetos') <div class="invalid-feedback">{{ $message }}</div> @enderror

                    <div class="row g-2 mt-1">
                        <div class="col-sm-6 col-xl-12">
                            <label class="form-label" for="fecha_inicio">Desde</label>
                            <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" value="{{ old('fecha_inicio') }}" class="form-control @error('fecha_inicio') is-invalid @enderror" required>
                            @error('fecha_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-6 col-xl-12">
                            <label class="form-label" for="fecha_fin">Hasta</label>
                            <input type="datetime-local" id="fecha_fin" name="fecha_fin" value="{{ old('fecha_fin') }}" class="form-control @error('fecha_fin') is-invalid @enderror" required>
                            @error('fecha_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <label class="form-label mt-2" for="motivo">Motivo</label>
                    <input type="text" id="motivo" name="motivo" value="{{ old('motivo') }}" placeholder="Ej: Mantenimiento de equipos" class="form-control @error('motivo') is-invalid @enderror" required>
                    @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror

                    @if (session('afectadas'))
                        <div class="mt-3">
                            <div class="form-check"><input class="form-check-input" type="radio" name="cancelar_afectadas" value="1" id="cancelarSi" checked>
                                <label class="form-check-label small" for="cancelarSi">Cancelarlas y avisar a sus dueños</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="cancelar_afectadas" value="0" id="cancelarNo">
                                <label class="form-check-label small" for="cancelarNo">Bloquear sin tocarlas</label></div>
                        </div>
                    @endif
                    <button class="btn btn-dark w-100 mt-3"><i class="bi bi-slash-circle"></i> Bloquear</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="d-flex justify-content-end mb-2">
            <a href="{{ request()->fullUrlWithQuery(['ver' => request('ver') === 'todos' ? null : 'todos']) }}" class="btn btn-sm btn-outline-secondary">{{ request('ver') === 'todos' ? 'Ver solo vigentes' : 'Ver también los pasados' }}</a>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Objeto</th><th>Desde</th><th>Hasta</th><th>Motivo</th><th>Creado por</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($bloqueos as $b)
                            <tr>
                                <td>{{ $b->objeto->nombre }}</td>
                                <td class="text-nowrap">{{ $b->fecha_inicio->format('d/m/Y H:i') }}</td>
                                <td class="text-nowrap">{{ $b->fecha_fin->format('d/m/Y H:i') }}</td>
                                <td>{{ $b->motivo }}</td>
                                <td><small>{{ $b->creador?->name ?? '—' }}</small></td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('bloqueos.destroy', $b) }}" onsubmit="return confirm('¿Quitar este bloqueo?')">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" aria-label="Quitar"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">No hay bloqueos vigentes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
