@extends('layouts.app')

@section('titulo', 'Listado de espacios y recursos')

@include('listados._datatables')

@section('contenido')
<div class="card">
    <div class="card-body">
        <table id="tablaObjetos" class="table table-striped align-middle w-100">
            <thead>
                <tr><th>Imagen</th><th>Nombre</th><th>Tipo</th><th>Categoría</th><th>Ubicación</th><th>Capacidad</th><th>Recursos asociados</th><th>Reservas</th><th>Habilitado</th></tr>
            </thead>
            <tbody>
                @foreach ($objetos as $o)
                    <tr>
                        <td><img src="{{ $o->imagen_url }}" class="miniatura" alt=""></td>
                        <td><a href="{{ route('objetos.show', $o) }}">{{ $o->nombre }}</a></td>
                        <td>{{ $o->tipo_nombre }}</td>
                        <td>{{ $o->categoria }}</td>
                        <td>{{ $o->ubicacion }}</td>
                        <td>{{ $o->capacidad ?? '—' }}</td>
                        <td>{{ $o->esEspacio() ? $o->recursos_asociados_count : '—' }}</td>
                        <td>{{ $o->reservas_count }}</td>
                        <td>{!! $o->disponible ? '<span class="badge text-bg-success">Sí</span>' : '<span class="badge text-bg-secondary">No</span>' !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>crearTabla('#tablaObjetos', { columnDefs: [{ targets: 0, orderable: false, searchable: false }], order: [[1, 'asc']] });</script>
@endpush
