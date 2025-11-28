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
        $validationCareerId = 'required|exists:careers,id';
        $validationName = 'required|string';
        $validationLocation = 'required|string|max:255';
        $validationEquipmentCount = 'required|integer|min:0';
        $validationStatus = 'in:activo,inactivo';
        $labId = $this->route('id');
        if ($labId) {
            $validationName = 'sometimes|string';
            $validationLocation = 'sometimes|string|max:255';
            $validationEquipmentCount = 'sometimes|integer|min:0';
        }
        return [
            'career_id' => $validationCareerId,
            'name' => $validationName,
            'location' => $validationLocation,
            'equipment_count' => $validationEquipmentCount,
            'status' => $validationStatus
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
            'career_id.required' => 'El ID de la carrera es obligatorio.',
            'career_id.exists' => 'La carrera especificada no existe.',
            'name.required' => 'El nombre del laboratorio es obligatorio.',
            'name.string' => 'El nombre del laboratorio debe ser una cadena de texto.',
            'location.required' => 'La ubicación del laboratorio es obligatoria.',
            'location.string' => 'La ubicación del laboratorio debe ser una cadena de texto.',
            'location.max' => 'La ubicación del laboratorio no puede exceder los 255 caracteres.',
            'equipment_count.required' => 'La cantidad de equipos es obligatoria.',
            'equipment_count.integer' => 'La cantidad de equipos debe ser un número entero.',
            'equipment_count.min' => 'La cantidad de equipos debe ser mayor o igual a 0.',
            'status.in' => 'El estado debe ser "activo" o "inactivo".',
        ];
    }
}
