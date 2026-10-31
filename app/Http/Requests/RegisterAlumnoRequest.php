<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterAlumnoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:80'],
            'apellido_paterno' => ['required', 'string', 'max:80'],
            'apellido_materno' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email', 'ends_with:@upemor.edu.mx'],
            'matricula' => ['required', 'string', 'max:25', 'unique:estudiantes,matricula'],
            'programa_id' => [
                'required',
                'integer',
                Rule::exists('programas_educativos', 'programa_id')->where(fn($query) => $query->where('activo', true)->whereIn('clave', \App\Http\Controllers\RegisterController::CARRERAS_PERMITIDAS)),
            ],
            'cuatrimestre' => ['nullable', 'integer', 'between:1,10'],
            'grupo' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'password_confirmation' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'email.required' => 'El correo institucional es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'email.ends_with' => 'Utiliza tu correo institucional de la universidad.',
            'matricula.required' => 'La matrícula es obligatoria.',
            'matricula.unique' => 'Esta matrícula ya está registrada.',
            'programa_id.required' => 'Selecciona tu programa educativo.',
            'programa_id.exists' => 'El programa seleccionado no está disponible.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
