<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidationAreas extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:areas,name',
            'description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Mensajes personalizados para las reglas de validación.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del área es obligatorio.',
            'name.unique' => 'Ya existe un área con este nombre.',
            'name.max' => 'El nombre del área no puede tener más de 255 caracteres.',
            'description.max' => 'La descripción no puede exceder los 500 caracteres.',
        ];
    }
}
