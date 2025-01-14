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
        $validationName = 'required|string|max:255|regex:/^[\pL\s,\-.]+$/u|unique:areas,name';
        $validationDescription = 'required|string|max:255|regex:/^[\pL\s,\-.]+$/u|unique:areas,name';
        $areaId = $this->route('id'); // Asegúrate de pasar el ID en la ruta
        if ($areaId){
            $validationName = 'required|string|max:255|regex:/^[\pL\s,\-.]+$/u|unique:areas,name';
            $validationDescription = 'required|string|max:255|regex:/^[\pL\s,\-.]+$/u|unique:areas,name';
        }
        return[
            'name' => $validationName,
            'description' => $validationDescription
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
            'description.regex' => 'La descripción solo puede contener letras, espacios, comas, guiones y puntos.',
            'description.regex' => 'Solo debe contener letras'
        ];
    }
}
