{{-- Datos básicos de la organización (registro y configuración). Requiere $tipos y opcional $org. --}}
@php($org = $org ?? null)
<div class="row g-3">
    <div class="col-md-8">
        <label for="nombre" class="form-label">Nombre de la organización</label>
        <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $org?->nombre) }}"
               class="form-control @error('nombre') is-invalid @enderror" required>
        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="nit" class="form-label">NIT <span class="text-muted">(opcional)</span></label>
        <input type="text" id="nit" name="nit" value="{{ old('nit', $org?->nit) }}" class="form-control @error('nit') is-invalid @enderror">
        @error('nit') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="tipo" class="form-label">Tipo</label>
        <select id="tipo" name="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
            <option value="">Elige…</option>
            @foreach ($tipos as $valor => $texto)
                <option value="{{ $valor }}" @selected(old('tipo', $org?->tipo) === $valor)>{{ $texto }}</option>
            @endforeach
        </select>
        @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="ciudad" class="form-label">Ciudad</label>
        <input type="text" id="ciudad" name="ciudad" value="{{ old('ciudad', $org?->ciudad) }}"
               class="form-control @error('ciudad') is-invalid @enderror" required>
        @error('ciudad') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="correo_contacto" class="form-label">Correo de contacto</label>
        <input type="email" id="correo_contacto" name="correo_contacto" value="{{ old('correo_contacto', $org?->correo_contacto) }}"
               class="form-control @error('correo_contacto') is-invalid @enderror" required>
        @error('correo_contacto') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="telefono_org" class="form-label">Teléfono <span class="text-muted">(opcional)</span></label>
        <input type="text" id="telefono_org" name="telefono" value="{{ old('telefono', $org?->telefono) }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label for="direccion" class="form-label">Dirección <span class="text-muted">(opcional)</span></label>
        <input type="text" id="direccion" name="direccion" value="{{ old('direccion', $org?->direccion) }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label for="sitio_web" class="form-label">Sitio web <span class="text-muted">(opcional)</span></label>
        <input type="url" id="sitio_web" name="sitio_web" value="{{ old('sitio_web', $org?->sitio_web) }}" placeholder="https://"
               class="form-control @error('sitio_web') is-invalid @enderror">
        @error('sitio_web') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label for="descripcion" class="form-label">Descripción <span class="text-muted">(opcional)</span></label>
        <textarea id="descripcion" name="descripcion" rows="2" class="form-control">{{ old('descripcion', $org?->descripcion) }}</textarea>
    </div>
</div>
