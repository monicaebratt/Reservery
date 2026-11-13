<?php

namespace App\Http\Requests;

use App\Models\ObjetoReservable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Validación de una solicitud de reserva, igual para la web y la API
class SolicitudReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('objetos')) {
            $this->merge([
                'objetos' => array_values(array_filter(array_merge(
                    [$this->input('espacio_id')],
                    (array) $this->input('recursos', []),
                ))),
            ]);
        }
    }

    public function rules(): array
    {
        $org = $this->user()->organizacion_id;

        return [
            'proposito_id' => ['required', Rule::exists('propositos', 'id')->where('organizacion_id', $org)],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'motivo' => ['required', 'string', 'max:255'],
            'espacio_id' => ['nullable', Rule::exists('objetos_reservables', 'id')->where('organizacion_id', $org)->where('tipo', 'espacio')],
            'recursos' => ['nullable', 'array'],
            'recursos.*' => [Rule::exists('objetos_reservables', 'id')->where('organizacion_id', $org)->where('tipo', 'recurso')],
            'objetos' => ['required', 'array', 'min:1'],
            'objetos.*' => ['integer', 'distinct', Rule::exists('objetos_reservables', 'id')->where('organizacion_id', $org)],
        ];
    }

    public function messages(): array
    {
        return [
            'objetos.required' => 'Elige al menos un espacio o recurso.',
            'objetos.min' => 'Elige al menos un espacio o recurso.',
            'proposito_id.required' => 'Indica el propósito de la reserva.',
        ];
    }

    public function datos(): array
    {
        return $this->safe()->only(['proposito_id', 'fecha', 'hora_inicio', 'hora_fin', 'motivo', 'objetos']);
    }
}
