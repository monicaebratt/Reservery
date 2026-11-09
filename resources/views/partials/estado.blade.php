{{-- Píldora de estado con ícono. Uso: @include('partials.estado', ['estado' => $reserva->estado]) --}}
@php($iconos = ['pendiente' => 'bi-hourglass-split', 'aprobada' => 'bi-check-circle', 'rechazada' => 'bi-x-circle', 'cancelada' => 'bi-slash-circle', 'completada' => 'bi-check2-all'])
<span class="estado estado-{{ $estado }}"><i class="bi {{ $iconos[$estado] ?? 'bi-circle' }}"></i>{{ ucfirst($estado) }}</span>
