@extends('layouts.app')

@section('titulo', 'Listado de reservas')

@include('listados._datatables')

@section('contenido')
<div class="card">
    <div class="card-body">
        <table id="tablaReservas" class="table table-striped align-middle w-100">
            <thead>
                <tr><th>#</th><th>Fecha</th><th>Horario</th><th>Motivo</th><th>Espacio / recursos</th><th>Propósito</th><th>Solicitante</th><th>Estado</th></tr>
            </thead>
            <tbody>
                @foreach ($reservas as $r)
                    <tr>
                        <td>{{ $r->id }}</td>
                        <td data-order="{{ $r->fecha->format('Y-m-d') }}">{{ $r->fecha->format('d/m/Y') }}</td>
                        <td>{{ $r->horario }}</td>
                        <td><a href="{{ route('reservas.show', $r) }}">{{ $r->motivo }}</a></td>
                        <td>{{ $r->objetos->pluck('nombre')->join(' + ') }}</td>
                        <td data-order="{{ $r->proposito->nivel_prioridad }}">{{ $r->proposito->nombre }}</td>
                        <td>{{ $r->usuario->name }}</td>
                        <td>@include('partials.estado', ['estado' => $r->estado])</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>crearTabla('#tablaReservas', { order: [[1, 'desc']] });</script>
@endpush
