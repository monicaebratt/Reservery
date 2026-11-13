@extends('layouts.app')

@section('titulo', 'Notificaciones')

@section('contenido')
<div class="d-flex justify-content-end mb-3">
    <form method="POST" action="{{ route('notificaciones.leer-todas') }}">@csrf
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-check2-all"></i> Marcar todas como leídas</button>
    </form>
</div>
<div class="card">
    <div class="list-group list-group-flush">
        @forelse ($notificaciones as $n)
            <form method="POST" action="{{ route('notificaciones.leer', $n) }}">@csrf @method('PATCH')
                <button class="list-group-item list-group-item-action d-flex gap-3 py-3 border-0 border-bottom {{ $n->leida ? 'text-muted' : '' }}">
                    <i class="bi bi-bell{{ $n->leida ? '' : '-fill text-primary' }} mt-1"></i>
                    <div class="text-start">
                        <div class="fw-semibold">{{ $n->titulo }}</div>
                        <div>{{ $n->mensaje }}</div>
                        <small class="text-muted">{{ $n->created_at->format('d/m/Y H:i') }} · {{ $n->created_at->diffForHumans() }}</small>
                    </div>
                </button>
            </form>
        @empty
            <div class="list-group-item text-center text-muted py-5">No tienes notificaciones.</div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $notificaciones->links() }}</div>
@endsection
