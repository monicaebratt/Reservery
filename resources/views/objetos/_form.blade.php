{{-- Inventario + horario operativo + recursos asociados para paquetes --}}
@csrf
<div class="row g-4">
    <div class="col-xl-7">
        <div class="card mb-4">
            <div class="card-header">Datos</div>
            <div class="card-body row g-3">
                <div class="col-md-8">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $objeto->nombre) }}" class="form-control @error('nombre') is-invalid @enderror" required>
                    @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select id="tipo" name="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
                        <option value="espacio" @selected(old('tipo', $objeto->tipo) === 'espacio')>Espacio</option>
                        <option value="recurso" @selected(old('tipo', $objeto->tipo) === 'recurso')>Recurso</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="categoria" class="form-label">Categoría</label>
                    <input type="text" id="categoria" name="categoria" list="listaCategorias" value="{{ old('categoria', $objeto->categoria) }}"
                           placeholder="Ej: Auditorio, Sala de reuniones, Proyector" class="form-control @error('categoria') is-invalid @enderror" required>
                    <datalist id="listaCategorias">@foreach ($categorias as $c) <option value="{{ $c }}"> @endforeach</datalist>
                    @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Objetos de la misma categoría se ofrecen como alternativa entre sí.</div>
                </div>
                <div class="col-md-6">
                    <label for="capacidad" class="form-label">Capacidad <span class="text-muted">(personas)</span></label>
                    <input type="number" min="1" id="capacidad" name="capacidad" value="{{ old('capacidad', $objeto->capacidad) }}" class="form-control @error('capacidad') is-invalid @enderror">
                    @error('capacidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="ubicacion" class="form-label">Ubicación</label>
                    <input type="text" id="ubicacion" name="ubicacion" value="{{ old('ubicacion', $objeto->ubicacion) }}" placeholder="Ej: Bloque B, piso 2" class="form-control">
                </div>
                <div class="col-8 col-md-4">
                    <label for="zona" class="form-label">Zona / sede</label>
                    <input type="text" id="zona" name="zona" value="{{ old('zona', $objeto->zona) }}" placeholder="Ej: Bloque B" class="form-control">
                </div>
                <div class="col-4 col-md-2">
                    <label for="piso" class="form-label">Piso</label>
                    <input type="number" id="piso" name="piso" value="{{ old('piso', $objeto->piso) }}" class="form-control">
                </div>
                <div class="col-12">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="2" class="form-control">{{ old('descripcion', $objeto->descripcion) }}</textarea>
                </div>
                <div class="col-md-8">
                    <label for="imagen" class="form-label">Imagen <span class="text-muted">(JPG, PNG o WEBP, máx. 2 MB)</span></label>
                    <input type="file" id="imagen" name="imagen" accept="image/*" class="form-control @error('imagen') is-invalid @enderror">
                    @error('imagen') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <img id="vistaPrevia" src="{{ $objeto->imagen_url }}" class="mt-2 rounded-3 {{ $objeto->exists ? '' : 'd-none' }}" style="max-height:140px" alt="Vista previa">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <input type="hidden" name="disponible" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="disponible" name="disponible" value="1" @checked(old('disponible', $objeto->disponible ?? true))>
                        <label class="form-check-label" for="disponible">Habilitado para reservar</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card mb-4">
            <div class="card-header">Horario operativo</div>
            <div class="card-body">
                @foreach ($horarios as $dia => $h)
                    @php($o = old("horarios.$dia", $h))
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <input type="hidden" name="horarios[{{ $dia }}][abre]" value="0">
                        <div class="form-check form-switch m-0" style="width:120px">
                            <input class="form-check-input" type="checkbox" role="switch" id="abre{{ $dia }}" name="horarios[{{ $dia }}][abre]" value="1" @checked(! empty($o['abre']))>
                            <label class="form-check-label small" for="abre{{ $dia }}">{{ $h['nombre'] }}</label>
                        </div>
                        <input type="time" name="horarios[{{ $dia }}][inicio]" value="{{ $o['inicio'] }}" class="form-control form-control-sm" aria-label="Abre el {{ $h['nombre'] }}">
                        <span class="small text-muted">a</span>
                        <input type="time" name="horarios[{{ $dia }}][fin]" value="{{ $o['fin'] }}" class="form-control form-control-sm" aria-label="Cierra el {{ $h['nombre'] }}">
                    </div>
                    @error("horarios.$dia.fin") <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                @endforeach
            </div>
        </div>

        <div class="card" id="cardRecursos">
            <div class="card-header">Recursos que se pueden reservar con este espacio</div>
            <div class="card-body">
                <p class="small text-muted">Forman los paquetes que los usuarios pueden armar en una sola reserva.</p>
                @forelse ($recursos as $r)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="recursos_asociados[]" value="{{ $r->id }}" id="rec{{ $r->id }}"
                               @checked(in_array($r->id, old('recursos_asociados', $objeto->exists ? $objeto->recursosAsociados->pluck('id')->all() : [])))>
                        <label class="form-check-label" for="rec{{ $r->id }}">{{ $r->nombre }} <small class="text-muted">· {{ $r->categoria }}</small></label>
                    </div>
                @empty
                    <p class="small text-muted mb-0">Aún no hay recursos en el inventario.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('imagen').addEventListener('change', (e) => {
        const img = document.getElementById('vistaPrevia');
        if (e.target.files[0]) { img.src = URL.createObjectURL(e.target.files[0]); img.classList.remove('d-none'); }
    });
    // Los paquetes solo aplican a espacios
    const tipo = document.getElementById('tipo');
    const mostrar = () => document.getElementById('cardRecursos').classList.toggle('d-none', tipo.value !== 'espacio');
    tipo.addEventListener('change', mostrar);
    mostrar();
</script>
@endpush
