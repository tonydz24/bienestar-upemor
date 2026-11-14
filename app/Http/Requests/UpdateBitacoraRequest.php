<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request para la Modificación de una Entrada Emocional Existente.
 *
 * Autoriza al alumno propietario del registro y valida emoción activa,
 * intensidad en escala Likert (1 a 5) y descripciones textuales.
 */
class UpdateBitacoraRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado tiene autorización para actualizar la bitácora específica.
     *
     * @return bool Verdadero si la Policy autoriza la edición.
     */
    public function authorize(): bool
    {
        $bitacora = $this->route('bitacora');
        return $bitacora && ($this->user()?->can('update', $bitacora) ?? false);
    }

    /**
     * Reglas de validación aplicadas a la actualización del registro emocional.
     *
     * @return array<string, array<int, mixed>> Matriz de reglas de validación.
     */
    public function rules(): array
    {
        return [
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
     * Mensajes de error personalizados para la actualización emocional.
     *
     * @return array<string, string> Matriz de mensajes descriptivos.
     */
    public function messages(): array
    {
        return [
            'emocion_id.required' => 'Selecciona una emoción válida.',
            'emocion_id.exists' => 'La emoción seleccionada no es válida.',
            'intensidad.required' => 'Indica qué tan intensa es esta emoción.',
            'intensidad.between' => 'La intensidad debe estar entre 1 (muy leve) y 5 (muy intensa).',
            'descripcion.max' => 'La descripción breve no debe superar los 255 caracteres.',
            'observaciones.max' => 'Las observaciones personales no deben superar los 2000 caracteres.',
        ];
    }
}
