<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidationLaboratorie extends FormRequest
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
        $validationName = 'required|string';
        $validationLocation = 'required|string|max:255';
        $validationEquipmentCount = 'required|integer|min:0';

        $labId = $this->route('id');
        if ($labId) {
            $validationName = 'sometimes|string';
            $validationLocation = 'sometimes|string|max:255';
            $validationEquipmentCount = 'sometimes|integer|min:0';
        }
        return [
            'name' => $validationName,
            'location' => $validationLocation,
            'equipment_count' => $validationEquipmentCount,
        ];
    }
    protected function prepareForValidation()
    {
        $this->merge([
            'name' => strtoupper(trim($this->input('name'))),
            'location' => trim($this->input('location')), // Si quieres también limpiar location.
        ]);
    }
    public function messages()
    {
        return [
            'name.required' => 'El nombre del laboratorio es obligatorio.',
            'name.string' => 'El nombre del laboratorio debe ser una cadena de texto.',
            'location.required' => 'La ubicación del laboratorio es obligatoria.',
            'location.string' => 'La ubicación del laboratorio debe ser una cadena de texto.',
            'location.max' => 'La ubicación del laboratorio no puede exceder los 255 caracteres.',
            'equipment_count.required' => 'La cantidad de equipos es obligatoria.',
            'equipment_count.integer' => 'La cantidad de equipos debe ser un número entero.',
            'equipment_count.min' => 'La cantidad de equipos debe ser mayor o igual a 0.',
        ];
    }
}
