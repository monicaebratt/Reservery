@extends('layouts.app')

@section('titulo', 'Propósitos y reglas de prioridad')

@section('contenido')
<p class="text-muted">Cuando dos solicitudes chocan, la de propósito con <strong>mayor prioridad</strong> puede desplazar a la de menor: el afectado recibe aviso y alternativas, y el cambio se confirma al aprobar la solicitud prioritaria. Con igual prioridad, gana quien reservó primero.</p>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">Orden de prioridad (de mayor a menor)</div>
            <ul class="list-group list-group-flush">
                @foreach ($propositos as $i => $p)
                    <li class="list-group-item">
                        <form method="POST" action="{{ route('propositos.update', $p) }}" class="row g-2 align-items-center">
                            @csrf @method('PUT')
                            <div class="col-auto"><span class="badge rounded-pill text-bg-dark">{{ $i + 1 }}</span></div>
                            <div class="col-auto"><input type="color" name="color" value="{{ $p->color }}" class="form-control form-control-color" title="Color" aria-label="Color"></div>
                            <div class="col"><input type="text" name="nombre" value="{{ $p->nombre }}" class="form-control form-control-sm fw-semibold" aria-label="Nombre">
                                <input type="text" name="descripcion" value="{{ $p->descripcion }}" placeholder="Descripción" class="form-control form-control-sm mt-1" aria-label="Descripción"></div>
                            <div class="col-auto" style="width:110px">
                                <label class="small text-muted d-block">Nivel</label>
                                <input type="number" name="nivel_prioridad" min="1" max="99" value="{{ $p->nivel_prioridad }}" class="form-control form-control-sm" aria-label="Nivel de prioridad">
                            </div>
                            <div class="col-auto text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" title="Guardar"><i class="bi bi-save"></i></button>
                            </div>
                        </form>
                        <div class="d-flex gap-1 mt-2 justify-content-end align-items-center">
                            <small class="text-muted me-auto">{{ $p->reservas_count }} reservas</small>
                            <form method="POST" action="{{ route('propositos.mover', [$p, 'subir']) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light" @disabled($loop->first) title="Subir prioridad"><i class="bi bi-arrow-up"></i></button></form>
                            <form method="POST" action="{{ route('propositos.mover', [$p, 'bajar']) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light" @disabled($loop->last) title="Bajar prioridad"><i class="bi bi-arrow-down"></i></button></form>
                            <form method="POST" action="{{ route('propositos.destroy', $p) }}" onsubmit="return confirm('¿Eliminar {{ $p->nombre }}?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger" title="Eliminar"><i class="bi bi-trash"></i></button></form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">Nuevo propósito</div>
            <div class="card-body">
                <form method="POST" action="{{ route('propositos.store') }}" novalidate>
                    @csrf
                    <label class="form-label" for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" class="form-control @error('nombre') is-invalid @enderror" required>
                    @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <label class="form-label mt-2" for="descripcion">Descripción</label>
                    <input type="text" id="descripcion" name="descripcion" value="{{ old('descripcion') }}" class="form-control">
                    <div class="row g-2 mt-1">
                        <div class="col-7"><label class="form-label" for="nivel">Nivel de prioridad</label>
                            <input type="number" id="nivel" min="1" max="99" name="nivel_prioridad" value="{{ old('nivel_prioridad', 1) }}" class="form-control @error('nivel_prioridad') is-invalid @enderror" required>
                            @error('nivel_prioridad') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                        <div class="col-5"><label class="form-label" for="color">Color</label>
                            <input type="color" id="color" name="color" value="{{ old('color', '#8061C5') }}" class="form-control form-control-color w-100"></div>
                    </div>
                    <button class="btn btn-primary w-100 mt-3">Agregar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
