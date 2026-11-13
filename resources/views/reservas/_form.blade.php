{{-- Reserva simple o compuesta en un solo formulario --}}
@csrf
@php
    $seleccion = old('objetos', $reserva->exists ? $reserva->objetos->pluck('id')->all() : array_filter([(int) ($preseleccion['objeto'] ?? 0)]));
    $espacioSel = old('espacio_id', $espacios->whereIn('id', $seleccion)->first()?->id);
    $recursosSel = old('recursos', $recursos->whereIn('id', $seleccion)->pluck('id')->all());
    $horaIni = old('hora_inicio', $reserva->hora_inicio ? substr($reserva->hora_inicio, 0, 5) : ($preseleccion['hora_inicio'] ?? ''));
    $horaFin = old('hora_fin', $reserva->hora_fin ? substr($reserva->hora_fin, 0, 5) : '');
    $fechaSel = old('fecha', $reserva->fecha?->format('Y-m-d') ?? ($preseleccion['fecha'] ?? today()->toDateString()));
@endphp

<div class="row g-4">
    <div class="col-xl-7 d-flex flex-column gap-4">
        <section class="card">
            <div class="card-body">
                <div class="paso-titulo"><span class="num">1</span> ¿Qué necesitas?</div>

                <label for="espacio_id" class="form-label fw-semibold">Espacio</label>
                <select id="espacio_id" name="espacio_id" class="form-select @error('espacio_id') is-invalid @enderror">
                    <option value="">Ninguno: solo necesito recursos</option>
                    @foreach ($espacios->groupBy('categoria') as $categoria => $grupo)
                        <optgroup label="{{ $categoria }}">
                            @foreach ($grupo as $e)
                                <option value="{{ $e->id }}" @selected($espacioSel == $e->id)
                                        data-imagen="{{ $e->imagen_url }}" data-capacidad="{{ $e->capacidad }}" data-ubicacion="{{ $e->ubicacion }}" data-categoria="{{ $e->categoria }}">{{ $e->nombre }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('espacio_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

                <div class="espacio-elegido mt-3 d-none" id="tarjetaEspacio">
                    <img src="" alt="" id="espacioImg">
                    <div>
                        <div class="fw-bold" id="espacioNombre"></div>
                        <div class="meta" id="espacioMeta"></div>
                    </div>
                </div>

                <div class="mt-4">
                    <span class="form-label fw-semibold d-block">Recursos <span class="text-muted small fw-normal" id="ayudaRecursos"></span></span>
                    <div class="d-flex flex-wrap gap-2" id="listaRecursos">
                        @foreach ($recursos as $r)
                            <div class="chip-check recurso-opcion" data-id="{{ $r->id }}">
                                <input type="checkbox" id="rec{{ $r->id }}" name="recursos[]" value="{{ $r->id }}" @checked(in_array($r->id, $recursosSel))>
                                <label for="rec{{ $r->id }}"><i class="bi bi-plus-lg"></i>{{ $r->nombre }}</label>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-muted small mt-2 mb-0 d-none" id="sinRecursos">Este espacio no tiene recursos asociados.</p>
                    @error('recursos') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                    @error('objetos') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-body">
                <div class="paso-titulo"><span class="num">2</span> ¿Para qué es?</div>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label for="proposito_id" class="form-label fw-semibold">Propósito</label>
                        <select id="proposito_id" name="proposito_id" class="form-select @error('proposito_id') is-invalid @enderror" required>
                            <option value="">Elige…</option>
                            @foreach ($propositos as $p)
                                <option value="{{ $p->id }}" @selected(old('proposito_id', $reserva->proposito_id) == $p->id)>{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                        @error('proposito_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-7">
                        <label for="motivo" class="form-label fw-semibold">Motivo</label>
                        <input type="text" id="motivo" name="motivo" value="{{ old('motivo', $reserva->motivo) }}" placeholder="Ej: Reunión del semillero"
                               class="form-control @error('motivo') is-invalid @enderror" required>
                        @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Horario --}}
    <div class="col-xl-5">
        <section class="card position-sticky" style="top: 20px">
            <div class="card-body">
                <div class="paso-titulo"><span class="num">3</span> Elige el horario</div>

                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fw-semibold" id="mesActual"></span>
                    <input type="date" id="fecha" name="fecha" min="{{ today()->toDateString() }}" value="{{ $fechaSel }}"
                           class="form-control form-control-sm ms-auto w-auto @error('fecha') is-invalid @enderror" required aria-label="Fecha">
                </div>
                @error('fecha') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                <div class="semana-tira mb-3" id="tiraSemana"></div>

                <div id="franjas"><p class="text-muted small mb-0">Elige un espacio o un recurso para ver sus horas libres.</p></div>
                <p class="small text-muted mt-1 mb-3 d-none" id="ayudaFranjas">Toca una hora libre. Para más tiempo, toca también la siguiente.</p>

                <div class="row g-2 align-items-end">
                    <div class="col-6">
                        <label for="hora_inicio" class="form-label small mb-1">Desde</label>
                        <input type="time" id="hora_inicio" name="hora_inicio" value="{{ $horaIni }}" class="form-control @error('hora_inicio') is-invalid @enderror" required>
                        @error('hora_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-6">
                        <label for="hora_fin" class="form-label small mb-1">Hasta</label>
                        <input type="time" id="hora_fin" name="hora_fin" value="{{ $horaFin }}" class="form-control @error('hora_fin') is-invalid @enderror" required>
                        @error('hora_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="panel-suave mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold small">Resumen</span>
                        <span id="estadoVerif" class="estado estado-cancelada">Sin revisar</span>
                    </div>
                    <div id="panelVerif" class="small text-muted">Cuando elijas qué y cuándo, revisamos que todo esté libre.</div>
                </div>

                <div class="mt-3">{{ $botones }}</div>
            </div>
        </section>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const paquetes = @json($paquetes);
        const $ = (id) => document.getElementById(id);
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const ignorar = {{ $reserva->exists ? $reserva->id : 'null' }};
        const HOY = '{{ today()->toDateString() }}';
        const esc = (t) => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const minutos = (h) => { const [a, b] = h.split(':').map(Number); return a * 60 + b; };
        const hora = (m) => String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0');
        const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        const objetosElegidos = () => [$('espacio_id').value, ...[...document.querySelectorAll('input[name="recursos[]"]:checked')].map(c => c.value)].filter(Boolean);

        // Espacio y recursos
        function mostrarEspacio() {
            const op = $('espacio_id').selectedOptions[0];
            const hay = !!$('espacio_id').value;
            $('tarjetaEspacio').classList.toggle('d-none', !hay);
            if (!hay) return;
            $('espacioImg').src = op.dataset.imagen;
            $('espacioNombre').textContent = op.textContent;
            $('espacioMeta').innerHTML = [
                op.dataset.capacidad && `<span><i class="bi bi-people"></i>${esc(op.dataset.capacidad)} personas</span>`,
                op.dataset.ubicacion && `<span><i class="bi bi-geo-alt"></i>${esc(op.dataset.ubicacion)}</span>`,
                `<span><i class="bi bi-tag"></i>${esc(op.dataset.categoria)}</span>`,
            ].filter(Boolean).join('');
        }

        function filtrarRecursos() {
            const espacio = $('espacio_id').value;
            const permitidos = espacio ? (paquetes[espacio] || []) : null;
            let visibles = 0;
            document.querySelectorAll('.recurso-opcion').forEach(op => {
                const ok = !permitidos || permitidos.includes(Number(op.dataset.id));
                op.classList.toggle('d-none', !ok);
                if (!ok) op.querySelector('input').checked = false;
                if (ok) visibles++;
            });
            $('sinRecursos').classList.toggle('d-none', visibles > 0);
            $('ayudaRecursos').textContent = espacio ? '· los que se pueden usar en este espacio' : '· recursos sueltos';
        }

        // Días de la semana y franjas libres
        function pintarSemana() {
            const f = new Date($('fecha').value + 'T00:00');
            const lunes = new Date(f); lunes.setDate(f.getDate() - ((f.getDay() + 6) % 7));
            const mes = f.toLocaleDateString('es-CO', { month: 'long', year: 'numeric' });
            $('mesActual').textContent = mes.charAt(0).toUpperCase() + mes.slice(1);
            let html = '<button type="button" class="flecha" data-mover="-7" aria-label="Semana anterior"><i class="bi bi-chevron-left"></i></button>';
            for (let i = 0; i < 7; i++) {
                const d = new Date(lunes); d.setDate(lunes.getDate() + i);
                const valor = iso(d);
                html += `<button type="button" class="dia ${valor === $('fecha').value ? 'activo' : ''}" data-fecha="${valor}" ${valor < HOY ? 'disabled' : ''}>
                    ${d.toLocaleDateString('es-CO', { weekday: 'short' }).replace('.', '')}<strong>${d.getDate()}</strong></button>`;
            }
            html += '<button type="button" class="flecha" data-mover="7" aria-label="Semana siguiente"><i class="bi bi-chevron-right"></i></button>';
            $('tiraSemana').innerHTML = html;
        }

        $('tiraSemana').addEventListener('click', (e) => {
            const b = e.target.closest('button');
            if (!b || b.disabled) return;
            if (b.dataset.mover) {
                const f = new Date($('fecha').value + 'T00:00');
                f.setDate(f.getDate() + Number(b.dataset.mover));
                $('fecha').value = iso(f) < HOY ? HOY : iso(f);
            } else {
                $('fecha').value = b.dataset.fecha;
            }
            alCambiarFecha();
        });

        async function cargarFranjas() {
            const objetos = objetosElegidos();
            if (!objetos.length || !$('fecha').value) {
                $('franjas').innerHTML = '<p class="text-muted small mb-0">Elige un espacio o un recurso para ver sus horas libres.</p>';
                $('ayudaFranjas').classList.add('d-none');
                return;
            }
            const p = new URLSearchParams({ fecha: $('fecha').value });
            objetos.forEach(id => p.append('objetos[]', id));
            try {
                const r = await fetch(`{{ route('disponibilidad.agenda') }}?${p}`, { headers: { Accept: 'application/json' } });
                const d = await r.json();
                pintarFranjas(d.objetos);
            } catch (e) { /* se reintenta al cambiar algo */ }
        }

        // Franjas de una hora libres para todos los objetos elegidos
        let libresPorHora = [];
        function pintarFranjas(objetos) {
            const cerrado = objetos.find(o => !o.disponible || o.festivo || !o.horario);
            if (cerrado) {
                $('franjas').innerHTML = `<p class="text-muted small mb-0">${esc(cerrado.nombre)} ${cerrado.festivo ? 'no abre por festivo' : (!cerrado.disponible ? 'está inhabilitado' : 'no abre este día')}. Prueba otro día.</p>`;
                $('ayudaFranjas').classList.add('d-none');
                return;
            }
            const abre = Math.max(...objetos.map(o => minutos(o.horario.inicio)));
            const cierra = Math.min(...objetos.map(o => minutos(o.horario.fin)));
            const libre = (ini, fin) => objetos.every(o => o.libre.some(l => minutos(l.inicio) <= ini && minutos(l.fin) >= fin));

            libresPorHora = [];
            let html = '';
            // En el día de hoy no se muestran las horas que ya pasaron
            const ahora = new Date();
            const desde = $('fecha').value === HOY ? Math.max(abre, ahora.getHours() * 60 + ahora.getMinutes()) : abre;
            for (let m = Math.ceil(desde / 60) * 60; m + 60 <= cierra; m += 60) {
                const ok = libre(m, m + 60);
                if (ok) libresPorHora.push(m);
                html += `<button type="button" class="franja-opcion" data-ini="${m}" ${ok ? '' : 'disabled'}>
                    <span><i class="bi bi-clock me-2" style="color:var(--rv-violeta)"></i>${hora(m)} – ${hora(m + 60)}</span>
                    <span class="${ok ? 'pildora-libre' : 'pildora-ocupado'}">${ok ? 'Libre' : 'Ocupado'}</span></button>`;
            }
            $('franjas').innerHTML = html ? `<div style="max-height: 300px; overflow-y: auto; padding-right: .25rem">${html}</div>` : '<p class="text-muted small mb-0">Ya no quedan horas para este día. Elige otro día arriba.</p>';
            $('ayudaFranjas').classList.toggle('d-none', !libresPorHora.length);
            marcarElegidas();
        }

        function marcarElegidas() {
            const ini = $('hora_inicio').value ? minutos($('hora_inicio').value) : null;
            const fin = $('hora_fin').value ? minutos($('hora_fin').value) : null;
            document.querySelectorAll('.franja-opcion').forEach(b => {
                const m = Number(b.dataset.ini);
                b.classList.toggle('elegida', ini !== null && fin !== null && m >= ini && m + 60 <= fin);
            });
        }

        $('franjas').addEventListener('click', (e) => {
            const b = e.target.closest('.franja-opcion');
            if (!b || b.disabled) return;
            const m = Number(b.dataset.ini);
            const ini = $('hora_inicio').value ? minutos($('hora_inicio').value) : null;
            const fin = $('hora_fin').value ? minutos($('hora_fin').value) : null;
            if (ini !== null && fin === m) {                 // alarga hacia adelante
                $('hora_fin').value = hora(m + 60);
            } else if (ini !== null && m + 60 === ini) {  // Alarga hacia atrás
                $('hora_inicio').value = hora(m);
            } else {  // Empieza de nuevo
                $('hora_inicio').value = hora(m);
                $('hora_fin').value = hora(m + 60);
            }
            marcarElegidas();
            programar();
        });

        // Verificación en vivo
        let temporizador;
        function programar() { clearTimeout(temporizador); temporizador = setTimeout(verificar, 350); }

        async function verificar() {
            const objetos = objetosElegidos();
            const fecha = $('fecha').value, ini = $('hora_inicio').value, fin = $('hora_fin').value;
            if (!fecha || !ini || !fin || !objetos.length) return;

            $('estadoVerif').className = 'estado estado-cancelada';
            $('estadoVerif').textContent = 'Revisando…';
            try {
                const r = await fetch('{{ route('disponibilidad.verificar') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ fecha, hora_inicio: ini, hora_fin: fin, objetos, ignorar }),
                });
                const d = await r.json();
                if (!r.ok) { $('panelVerif').innerHTML = esc(d.message || 'Revisa los datos.'); $('estadoVerif').textContent = 'Revisa'; return; }

                const dia = new Date(fecha + 'T00:00').toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long' });
                $('estadoVerif').className = 'estado ' + (d.disponible ? 'estado-aprobada' : 'estado-rechazada');
                $('estadoVerif').innerHTML = d.disponible ? '<i class="bi bi-check-circle"></i>Todo libre' : '<i class="bi bi-x-circle"></i>No disponible';
                $('panelVerif').innerHTML = `<div class="mb-2 text-body"><i class="bi bi-calendar3 me-1" style="color:var(--rv-violeta)"></i>${dia}, ${ini} – ${fin}</div>`
                    + d.objetos.map(o => `<div class="componente"><span>${esc(o.nombre)}</span>${o.libre ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>'}</div>`).join('')
                    + (d.disponible ? '' : `<div class="text-danger mt-2">${d.problemas.map(p => esc(p.detalle)).join('<br>')}</div>
                        <div class="mt-2">Si envías igual, te mostraremos opciones parecidas o la lista de espera.</div>`);
            } catch (e) {
                $('estadoVerif').textContent = 'Sin conexión';
            }
        }

        function alCambiarFecha() { pintarSemana(); cargarFranjas(); programar(); }

        $('espacio_id').addEventListener('change', () => { mostrarEspacio(); filtrarRecursos(); cargarFranjas(); programar(); });
        $('fecha').addEventListener('change', alCambiarFecha);
        ['hora_inicio', 'hora_fin'].forEach(id => $(id).addEventListener('change', () => { marcarElegidas(); programar(); }));
        $('listaRecursos').addEventListener('change', () => { cargarFranjas(); programar(); });
        if ($('hora_inicio').value && !$('hora_fin').value) $('hora_fin').value = hora(minutos($('hora_inicio').value) + 60);
        mostrarEspacio();
        filtrarRecursos();
        pintarSemana();
        cargarFranjas();
        verificar();
    })();
</script>
@endpush
