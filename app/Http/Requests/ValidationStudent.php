<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidationStudent extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ci' => ['required', 'regex:/^[0-9]{7,8}$/', 'unique:students,ci'],
            'name' => ['required', 'string', 'max:255'],
            'paternal_surname' => ['nullable', 'string', 'max:25'], // ahora nullable
            'maternal_surname' => ['nullable', 'string', 'max:25'], // ahora nullable
            'phone_number' => ['required', 'integer', 'unique:students,phone_number'],
            'birthdate' => ['required', 'date', 'before:today'],
        ];
    }

    public function messages()
    {
        return [
            'ci.required' => 'El CI es obligatorio.',
            'ci.regex' => 'El CI debe tener 7 o 8 dígitos.',
            'ci.unique' => 'Este CI ya está registrado.',
            'name.required' => 'El nombre es obligatorio.',
            'phone_number.required' => 'El número de teléfono es obligatorio.',
            'phone_number.integer' => 'El número de teléfono debe ser un número entero.',
            'phone_number.max' => 'La longitud máxima del número de teléfono es de 20 caracteres.',
            'phone_number.unique' => 'Este número de teléfono ya está registrado.',
            'birthdate.required' => 'La fecha de nacimiento es obligatoria.',
            'birthdate.before' => 'La fecha de nacimiento debe ser anterior a la fecha actual.',
            'surname.required' => 'Debe ingresar al menos un apellido (paterno o materno).',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $paterno = $this->input('paternal_surname');
            $materno = $this->input('maternal_surname');

            if (empty($paterno) && empty($materno)) {
                $validator->errors()->add('surname', 'Debe ingresar al menos un apellido (paterno o materno).');
            }
        });
    }
}
