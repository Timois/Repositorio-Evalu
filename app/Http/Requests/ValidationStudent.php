<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidationStudent extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ci' => ['required', 'regex:/^[0-9]{7,8}$/', 'unique:students,ci'],
            'name' => ['required', 'string', 'max:255'],
            'paternal_surname' => ['required', 'string', 'max:25'],
            'maternal_surname' => ['required', 'string', 'max:25'],
            'phone_number' => ['required', 'regex:/^[0-9]{9}$/'],
            'birthdate' => ['required', 'date', 'before:today'],
            // Removemos las validaciones de password ya que ahora se genera automáticamente
            'status' => ['required', 'in|activo,inactivo']
        ];
    }

    public function messages()
    {
        return [
            'ci.required' => 'El CI es obligatorio.',
            'ci.regex' => 'El CI debe tener 7 o 8 dígitos.',
            'ci.unique' => 'Este CI ya está registrado.',
            'name.required' => 'El nombre es obligatorio.',
            'paternal_surname.required' => 'El apellido paterno es obligatorio.',
            'maternal_surname.required' => 'El apellido materno es obligatorio.',
            'phone_number.required' => 'El número de teléfono es obligatorio.',
            'phone_number.regex' => 'El número de teléfono debe tener 9 dígitos.',
            'birthdate.required' => 'La fecha de nacimiento es obligatoria.',
            'birthdate.before' => 'La fecha de nacimiento debe ser anterior a la fecha actual.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser "activo" o "inactivo".'
        ];
    }
}
