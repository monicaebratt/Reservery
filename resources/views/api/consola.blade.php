@extends('layouts.app')

@section('titulo', 'API y tokens')

@section('contenido')
<div class="row g-4">
    <div class="col-xl-4">
        <div class="card mb-4">
            <div class="card-header">1. Crea un token de acceso</div>
            <div class="card-body">
                <form method="POST" action="{{ route('api.tokens.store') }}" class="d-flex gap-2">
                    @csrf
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" placeholder="Ej: Postman" required>
                    <button class="btn btn-primary text-nowrap">Crear</button>
                </form>
                @if (session('token_nuevo'))
                    <div class="alert alert-warning mt-3 mb-0 small">
                        <strong>Tu token:</strong>
                        <code class="d-block text-break user-select-all mt-1" id="tokenNuevo">{{ session('token_nuevo') }}</code>
                    </div>
                @endif
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($tokens as $t)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><div class="fw-semibold">{{ $t->name }}</div>
                            <small class="text-muted">Creado {{ $t->created_at->diffForHumans() }} · {{ $t->last_used_at ? 'usado '.$t->last_used_at->diffForHumans() : 'sin uso' }}</small></div>
                        <form method="POST" action="{{ route('api.tokens.destroy', $t->id) }}">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Revocar"><i class="bi bi-trash"></i></button>
                        </form>
                    </li>
                @empty
                    <li class="list-group-item text-muted small">Aún no tienes tokens.</li>
                @endforelse
            </ul>
        </div>

        <div class="card">
            <div class="card-header">Principales endpoints</div>
            <div class="card-body small">
                @foreach ([
                    ['POST', '/api/auth/login', 'token con correo y contraseña'],
                    ['POST', '/api/auth/google', 'token con un access_token de Google'],
                    ['POST', '/api/organizaciones', 'registrar organización'],
                    ['GET', '/api/disponibilidad?fecha=…', 'agenda del día'],
                    ['POST', '/api/disponibilidad/verificar', 'revisar una solicitud'],
                    ['GET', '/api/reservas', 'historial'],
                    ['POST', '/api/reservas', 'reservar (409 + alternativas si no hay cupo)'],
                    ['PUT', '/api/reservas/{id}', 'editar'],
                    ['PATCH', '/api/reservas/{id}/estado', 'aprobar, rechazar, cancelar, completar'],
                    ['DELETE', '/api/reservas/{id}', 'eliminar'],
                    ['POST', '/api/alternativas', 'equivalentes libres'],
                    ['POST', '/api/lista-espera', 'anotarse'],
                    ['GET', '/api/notificaciones', 'notificaciones'],
                    ['GET', '/api/objetos', 'inventario'],
                    ['GET', '/api/analitica/ocupacion', 'reporte (admin)'],
                ] as [$m, $ruta, $texto])
                    <p class="mb-2"><span class="badge text-bg-{{ ['GET' => 'primary', 'POST' => 'success', 'PUT' => 'warning', 'PATCH' => 'warning', 'DELETE' => 'danger'][$m] }}">{{ $m }}</span> <code>{{ $ruta }}</code> <span class="text-muted">{{ $texto }}</span></p>
                @endforeach
                <p class="mb-0 text-muted">Las rutas protegidas exigen las cabeceras <code>Authorization: Bearer {token}</code> y <code>Accept: application/json</code>. El listado completo está en el README.</p>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">2. Consume la API REST</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small text-muted">Token (cabecera Authorization)</label>
                    <input type="text" id="token" class="form-control font-monospace" placeholder="Pega aquí tu token" value="{{ session('token_nuevo') }}">
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="small text-muted align-self-center">Ejemplos:</span>
                    <button class="btn btn-sm btn-outline-primary" data-ejemplo="listar">GET reservas</button>
                    <button class="btn btn-sm btn-outline-primary" data-ejemplo="agenda">GET disponibilidad</button>
                    <button class="btn btn-sm btn-outline-success" data-ejemplo="crear">POST reservar</button>
                    <button class="btn btn-sm btn-outline-warning" data-ejemplo="editar">PUT editar</button>
                    <button class="btn btn-sm btn-outline-warning" data-ejemplo="cancelar">PATCH cancelar</button>
                    <button class="btn btn-sm btn-outline-danger" data-ejemplo="eliminar">DELETE</button>
                    <button class="btn btn-sm btn-outline-success" data-ejemplo="alternativas">POST alternativas</button>
                </div>
                <div class="input-group mb-3">
                    <select id="metodo" class="form-select" style="max-width:120px">
                        <option>GET</option><option>POST</option><option>PUT</option><option>PATCH</option><option>DELETE</option>
                    </select>
                    <input type="text" id="ruta" class="form-control font-monospace" value="/api/reservas">
                    <button id="enviar" class="btn btn-primary"><i class="bi bi-send"></i> Enviar</button>
                </div>
                <label class="form-label small text-muted">Cuerpo JSON (para POST, PUT y PATCH)</label>
                <textarea id="cuerpo" class="form-control font-monospace mb-3" rows="7"></textarea>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small text-muted">Respuesta</span>
                    <span id="estado" class="badge text-bg-light">—</span>
                </div>
                <pre class="respuesta" id="respuesta">Aquí verás la respuesta del servidor.</pre>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const $ = (id) => document.getElementById(id);
    const manana = new Date(Date.now() + 86400000).toISOString().slice(0, 10);

    const proposito = {{ \App\Models\Proposito::orderBy('nivel_prioridad')->value('id') ?? 1 }};
    const objeto = {{ \App\Models\ObjetoReservable::espacios()->value('id') ?? 1 }};
    let ultima = {{ auth()->user()->reservas()->latest('id')->value('id') ?? 1 }}; // se actualiza al crear una
    const solicitud = (ini, fin, motivo) => ({ proposito_id: proposito, fecha: manana, hora_inicio: ini, hora_fin: fin, motivo, objetos: [objeto] });
    const ejemplos = () => ({
        listar:       ['GET', '/api/reservas', ''],
        agenda:       ['GET', `/api/disponibilidad?fecha=${manana}`, ''],
        crear:        ['POST', '/api/reservas', solicitud('19:00', '20:00', 'Reserva creada desde la API')],
        editar:       ['PUT', `/api/reservas/${ultima}`, solicitud('20:00', '21:00', 'Reserva editada desde la API')],
        cancelar:     ['PATCH', `/api/reservas/${ultima}/estado`, { accion: 'cancelar', motivo: 'Prueba desde la API' }],
        eliminar:     ['DELETE', `/api/reservas/${ultima}`, ''],
        alternativas: ['POST', '/api/alternativas', { fecha: manana, hora_inicio: '08:00', hora_fin: '10:00', objetos: [objeto] }],
    });

    document.querySelectorAll('[data-ejemplo]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const [metodo, ruta, cuerpo] = ejemplos()[boton.dataset.ejemplo];
            $('metodo').value = metodo;
            $('ruta').value = ruta;
            $('cuerpo').value = cuerpo ? JSON.stringify(cuerpo, null, 2) : '';
        });
    });

    $('enviar').addEventListener('click', async () => {
        const metodo = $('metodo').value;
        const opciones = {
            method: metodo,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + $('token').value.trim(),
            },
        };
        if (['POST', 'PUT', 'PATCH'].includes(metodo) && $('cuerpo').value.trim()) {
            opciones.body = $('cuerpo').value;
        }

        $('respuesta').textContent = 'Enviando…';
        try {
            const res = await fetch($('ruta').value, opciones);
            const texto = await res.text();
            $('estado').textContent = res.status + ' ' + res.statusText;
            $('estado').className = 'badge text-bg-' + (res.ok ? 'success' : 'danger');
            try {
                const json = JSON.parse(texto);
                // Si se acaba de crear una reserva, los ejemplos de editar, cancelar y eliminar la usan
                if (metodo === 'POST' && $('ruta').value === '/api/reservas' && json.data?.id) ultima = json.data.id;
                $('respuesta').textContent = JSON.stringify(json, null, 2);
            }
            catch { $('respuesta').textContent = texto; }
        } catch (e) {
            $('estado').textContent = 'Error';
            $('respuesta').textContent = e.message;
        }
    });
</script>
@endpush
