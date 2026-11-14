<?php

namespace App\Http\Requests;

use App\Http\Controllers\RegisterController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Form Request para la Validación del Registro Público de Alumnos.
 *
 * Aplica saneamiento previo (mayúsculas en matrícula/grupo, minúsculas en email institucional)
 * y valida reglas estrictas de dominio (@upemor.edu.mx), longitud de matrícula (10 caracteres alfanuméricos),
 * programa educativo activo y seguridad de contraseñas (mínimo 12 caracteres).
 */
class RegisterAlumnoRequest extends FormRequest
{
    /**
     * Determina si el usuario no autenticado tiene autorización para enviar este formulario público.
     *
     * @return bool Siempre verdadero para registro público institucional.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepara y sanitiza los campos de entrada antes de aplicar las reglas de validación.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower(trim((string) $this->input('email'))),
            ]);
        }
        if ($this->has('matricula')) {
            $this->merge([
                'matricula' => Str::upper(trim((string) $this->input('matricula'))),
            ]);
        }
        if ($this->has('grupo')) {
            $this->merge([
                'grupo' => Str::upper(trim((string) $this->input('grupo'))),
            ]);
        }
        if ($this->has('nombre')) {
            $this->merge([
                'nombre' => trim((string) $this->input('nombre')),
            ]);
        }
        if ($this->has('apellido_paterno')) {
            $this->merge([
                'apellido_paterno' => trim((string) $this->input('apellido_paterno')),
            ]);
        }
        if ($this->has('apellido_materno') && $this->input('apellido_materno') !== null) {
            $this->merge([
                'apellido_materno' => trim((string) $this->input('apellido_materno')),
            ]);
        }
    }

    /**
     * Reglas de validación aplicadas a los datos de la solicitud de registro.
     *
     * @return array<string, array<int, mixed>> Matriz asociativa de reglas de validación.
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:80'],
            'apellido_paterno' => ['required', 'string', 'max:80'],
            'apellido_materno' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email', 'ends_with:@upemor.edu.mx'],
            'matricula' => ['required', 'string', 'size:10', 'regex:/^[A-Z0-9]{10}$/', 'unique:estudiantes,matricula'],
            'programa_id' => [
                'required',
                'integer',
                Rule::exists('programas_educativos', 'programa_id')->where(fn($query) => $query->where('activo', true)->whereIn('clave', RegisterController::CARRERAS_PERMITIDAS)),
            ],
            'cuatrimestre' => ['required', 'integer', 'between:1,10'],
            'grupo' => ['required', 'string', Rule::in(['A', 'B', 'C', 'D', 'E', 'F'])],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'password_confirmation' => ['required'],
        ];
    }

    /**
     * Mensajes de error personalizados para retroalimentación clara al aspirante.
     *
     * @return array<string, string> Matriz de mensajes de validación en español.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'email.required' => 'El correo institucional es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'email.ends_with' => 'Utiliza tu correo institucional de la universidad (@upemor.edu.mx).',
            'matricula.required' => 'La matrícula es obligatoria.',
            'matricula.size' => 'La matrícula debe tener exactamente 10 caracteres.',
            'matricula.regex' => 'La matrícula debe contener únicamente letras y números (10 caracteres).',
            'matricula.unique' => 'Esta matrícula ya está registrada.',
            'programa_id.required' => 'Selecciona tu carrera.',
            'programa_id.exists' => 'El programa seleccionado no está disponible.',
            'cuatrimestre.required' => 'El cuatrimestre es obligatorio.',
            'cuatrimestre.integer' => 'El cuatrimestre debe ser un número entero.',
            'cuatrimestre.between' => 'Selecciona un cuatrimestre válido del 1 al 10.',
            'grupo.required' => 'El grupo es obligatorio.',
            'grupo.in' => 'Selecciona un grupo válido (A, B, C, D, E o F).',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password_confirmation.required' => 'Confirma tu contraseña.',
        ];
    }
}
