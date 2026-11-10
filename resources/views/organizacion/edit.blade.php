@extends('layouts.app')

@section('titulo', 'Configuración de la organización')

@section('contenido')
@php
    $pasos = [
        ['Datos básicos', filled($org->nombre) && filled($org->ciudad) && filled($org->correo_contacto)],
        ['Horario operativo', filled($org->dias_operativos)],
        ['Acceso habilitado', $org->configurada],
    ];
@endphp

<div class="row g-4">
    <div class="col-xl-8">
        <form method="POST" action="{{ route('organizacion.update') }}" enctype="multipart/form-data" novalidate>
            @csrf @method('PUT')
            <div class="card mb-4">
                <div class="card-header">Datos básicos</div>
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                        @if ($org->logo_url)
                            <img src="{{ $org->logo_url }}" alt="Logo" class="rounded-3 border" width="72" height="72" style="object-fit:contain">
                        @else
                            <span class="org-inicial" style="width:72px;height:72px;font-size:1.8rem">{{ mb_substr($org->nombre, 0, 1) }}</span>
                        @endif
                        <div class="flex-grow-1">
                            <label for="logo" class="form-label">Logo <span class="text-muted">(opcional, máx. 2 MB)</span></label>
                            <input type="file" id="logo" name="logo" accept="image/*" class="form-control @error('logo') is-invalid @enderror">
                            @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    @include('organizacion._datos')
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Horario y reglas de reserva</div>
                <div class="card-body">
                    <p class="small text-muted">Este horario se aplica a cada espacio o recurso nuevo. Después puedes ajustarlo por objeto.</p>
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <label for="hora_apertura" class="form-label">Abre</label>
                            <input type="time" id="hora_apertura" name="hora_apertura" value="{{ old('hora_apertura', substr($org->hora_apertura, 0, 5)) }}"
                                   class="form-control @error('hora_apertura') is-invalid @enderror" required>
                            @error('hora_apertura') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="hora_cierre" class="form-label">Cierra</label>
                            <input type="time" id="hora_cierre" name="hora_cierre" value="{{ old('hora_cierre', substr($org->hora_cierre, 0, 5)) }}"
                                   class="form-control @error('hora_cierre') is-invalid @enderror" required>
                            @error('hora_cierre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-block">Días operativos</label>
                            @foreach ($dias as $n => $dia)
                                <input type="checkbox" class="btn-check" name="dias_operativos[]" value="{{ $n }}" id="dia{{ $n }}"
                                       @checked(in_array($n, old('dias_operativos', $org->dias_operativos ?? [])))>
                                <label class="btn btn-sm btn-outline-primary mb-1" for="dia{{ $n }}">{{ mb_substr($dia, 0, 3) }}</label>
                            @endforeach
                            @error('dias_operativos') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <hr>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="hidden" name="requiere_aprobacion" value="0">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="requiere_aprobacion" name="requiere_aprobacion" value="1" @checked(old('requiere_aprobacion', $org->requiere_aprobacion))>
                                <label class="form-check-label" for="requiere_aprobacion">Las reservas necesitan aprobación de un administrador</label>
                            </div>
                            <input type="hidden" name="cerrar_en_festivos" value="0">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="cerrar_en_festivos" name="cerrar_en_festivos" value="1" @checked(old('cerrar_en_festivos', $org->cerrar_en_festivos))>
                                <label class="form-check-label" for="cerrar_en_festivos">No abrir los festivos de Colombia</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="umbral_subutilizacion" class="form-label">Considerar "subutilizado" por debajo de</label>
                            <div class="input-group" style="max-width:180px">
                                <input type="number" min="1" max="100" id="umbral_subutilizacion" name="umbral_subutilizacion" value="{{ old('umbral_subutilizacion', $org->umbral_subutilizacion) }}" class="form-control">
                                <span class="input-group-text">% de uso</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar configuración</button>
        </form>
    </div>

    <div class="col-xl-4 d-flex flex-column gap-4">
        <div class="card">
            <div class="card-header">Estado</div>
            <div class="card-body d-flex flex-column gap-3">
                @foreach ($pasos as [$texto, $hecho])
                    <div class="paso align-items-center">
                        <span class="paso-icono {{ $hecho ? 'bg-success-subtle text-success' : 'bg-light text-muted' }}"><i class="bi {{ $hecho ? 'bi-check-lg' : 'bi-circle' }}"></i></span>
                        <span class="{{ $hecho ? '' : 'text-muted' }}">{{ $texto }}</span>
                    </div>
                @endforeach

                @unless ($org->configurada)
                    <form method="POST" action="{{ route('organizacion.habilitar') }}" id="habilitar">
                        @csrf
                        <button class="btn btn-success w-100"><i class="bi bi-unlock"></i> Habilitar acceso de miembros</button>
                    </form>
                    @error('configurada') <div class="text-danger small">{{ $message }}</div> @enderror
                    <p class="small text-muted mb-0">Mientras no lo habilites, nadie más puede unirse ni reservar.</p>
                @endunless
            </div>
        </div>

        <div class="card">
            <div class="card-header">Código de invitación</div>
            <div class="card-body text-center">
                <div class="codigo-invitacion user-select-all">{{ $org->codigo_invitacion }}</div>
                <p class="small text-muted mt-2">Compártelo con tus miembros: lo escriben al registrarse.</p>
                <form method="POST" action="{{ route('organizacion.codigo') }}" onsubmit="return confirm('El código actual dejará de servir. ¿Continuar?')">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-repeat"></i> Generar uno nuevo</button>
                </form>
            </div>
        </div>

        <a href="{{ route('miembros.index') }}" class="btn btn-outline-primary"><i class="bi bi-people"></i> Miembros y administradores</a>
    </div>
</div>
@endsection
