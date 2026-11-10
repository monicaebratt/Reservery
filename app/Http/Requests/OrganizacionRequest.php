<?php

namespace App\Http\Requests;

use App\Models\Organizacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Datos básicos y configuración de la organización (RF01.1). */
class OrganizacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('dias_operativos')) {
            $this->merge(['dias_operativos' => array_map('intval', (array) $this->input('dias_operativos'))]);
        }
    }

    public function rules(): array
    {
        $id = $this->user()?->organizacion_id;
        $esConfiguracion = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'nit' => ['nullable', 'string', 'max:30', Rule::unique('organizaciones', 'nit')->ignore($esConfiguracion ? $id : null)],
            'tipo' => ['required', Rule::in(array_keys(Organizacion::TIPOS))],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'ciudad' => ['required', 'string', 'max:80'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo_contacto' => ['required', 'email', 'max:150'],
            'sitio_web' => ['nullable', 'url', 'max:200'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            // Configuración operativa
            'hora_apertura' => [$esConfiguracion ? 'required' : 'nullable', 'date_format:H:i'],
            'hora_cierre' => [$esConfiguracion ? 'required' : 'nullable', 'date_format:H:i', 'after:hora_apertura'],
            'dias_operativos' => [$esConfiguracion ? 'required' : 'nullable', 'array', 'min:1'],
            'dias_operativos.*' => ['integer', 'between:1,7'],
            'cerrar_en_festivos' => ['sometimes', 'boolean'],
            'requiere_aprobacion' => ['sometimes', 'boolean'],
            'umbral_subutilizacion' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre de la organización',
            'correo_contacto' => 'correo de contacto',
            'hora_apertura' => 'hora de apertura',
            'hora_cierre' => 'hora de cierre',
            'dias_operativos' => 'días operativos',
            'sitio_web' => 'sitio web',
        ];
    }

    public function datos(): array
    {
        $datos = collect($this->validated())->except('logo')->all();

        foreach (['cerrar_en_festivos', 'requiere_aprobacion'] as $campo) {
            if ($this->has($campo)) {
                $datos[$campo] = $this->boolean($campo);
            }
        }

        return array_filter($datos, fn ($v) => $v !== null);
    }
}
