<?php

namespace App\Http\Requests;

use App\Models\BitacoraEmocional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Form Request para el Registro Diario de Estado Emocional en la Bitácora.
 *
 * Valida fecha válida (no futura), emoción activa del catálogo institucional,
 * escala Likert de intensidad (1 a 5), longitud de descripciones y unicidad
 * diaria por estudiante para evitar duplicados en la misma fecha.
 */
class StoreBitacoraRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado tiene permiso según la policy para crear bitácoras.
     *
     * @return bool Verdadero si está autorizado por la Gate/Policy.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', BitacoraEmocional::class) ?? false;
    }

    /**
     * Reglas de validación aplicables a la entrada de registro emocional.
     *
     * @return array<string, array<int, mixed>> Matriz de reglas de validación.
     */
    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
            'emocion_id' => [
                'required',
                'integer',
                Rule::exists('emociones', 'emocion_id')->where('activo', true),
            ],
            'intensidad' => ['required', 'integer', 'between:1,5'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Hook posterior a la validación que verifica unicidad de registro por estudiante y fecha.
     *
     * @param Validator $validator Instancia del validador de Laravel.
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('fecha') && $this->user()) {
                $existe = BitacoraEmocional::where('usuario_id', $this->user()->usuario_id)
                    ->where('fecha', $this->fecha)
                    ->exists();

                if ($existe) {
                    $validator->errors()->add(
                        'fecha',
                        'Ya registraste tu estado emocional para este día. Puedes editar el registro existente si tus emociones cambiaron.'
                    );
                }
            }
        });
    }

    /**
     * Mensajes de error personalizados para el registro emocional.
     *
     * @return array<string, string> Matriz de mensajes de error en español.
     */
    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'Ingresa una fecha válida.',
            'fecha.date_format' => 'El formato de fecha debe ser AAAA-MM-DD.',
            'fecha.before_or_equal' => 'No es posible registrar emociones en fechas futuras.',
            'emocion_id.required' => 'Selecciona cómo te sientes hoy.',
            'emocion_id.exists' => 'La emoción seleccionada no es válida.',
            'intensidad.required' => 'Indica qué tan intensa es esta emoción.',
            'intensidad.between' => 'La intensidad debe estar entre 1 (muy leve) y 5 (muy intensa).',
            'descripcion.max' => 'La descripción breve no debe superar los 255 caracteres.',
            'observaciones.max' => 'Las observaciones personales no deben superar los 2000 caracteres.',
        ];
    }
}
