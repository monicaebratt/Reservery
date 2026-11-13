<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Inventario con horario operativo y recursos asociados
class ObjetoReservableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $org = $this->user()->organizacion_id;

        return [
            'nombre' => ['required', 'string', 'max:120'],
            'tipo' => ['required', Rule::in(['espacio', 'recurso'])],
            'categoria' => ['required', 'string', 'max:60'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'capacidad' => ['nullable', 'integer', 'min:1'],
            'ubicacion' => ['nullable', 'string', 'max:150'],
            'zona' => ['nullable', 'string', 'max:60'],
            'piso' => ['nullable', 'integer', 'between:-5,100'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'disponible' => ['sometimes', 'boolean'],
            // Horario: horarios[1..7][abre|inicio|fin]
            'horarios' => ['nullable', 'array'],
            'horarios.*.abre' => ['nullable', 'boolean'],
            'horarios.*.inicio' => ['nullable', 'required_if_accepted:horarios.*.abre', 'date_format:H:i'],
            'horarios.*.fin' => ['nullable', 'required_if_accepted:horarios.*.abre', 'date_format:H:i'],
            // Paquetes: solo para espacios
            'recursos_asociados' => ['nullable', 'array'],
            'recursos_asociados.*' => [Rule::exists('objetos_reservables', 'id')->where('organizacion_id', $org)->where('tipo', 'recurso')],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            foreach ((array) $this->input('horarios', []) as $dia => $h) {
                if (! empty($h['abre']) && ! empty($h['inicio']) && ! empty($h['fin']) && $h['fin'] <= $h['inicio']) {
                    $validator->errors()->add("horarios.{$dia}.fin", 'La hora de cierre debe ser posterior a la de apertura.');
                }
            }
        }];
    }

    public function datos(): array
    {
        $datos = collect($this->validated())->only([
            'nombre', 'tipo', 'categoria', 'descripcion', 'capacidad', 'ubicacion', 'zona', 'piso',
        ])->all();
        $datos['disponible'] = $this->has('disponible') ? $this->boolean('disponible') : true;

        return $datos;
    }
}
