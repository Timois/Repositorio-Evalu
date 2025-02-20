<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $careerId = $this->input('career_id');
        $validationName = [
            'required',
            'string',
            'max:50',
            'regex:/^[\pL\s,\-.]+$/u',
            Rule::unique('areas')->where(function ($query) use ($careerId) {
                return $query->where('career_id', $careerId);
            }),
        ];
        
        $validationDescription = 'string|max:255|regex:/^[\pL\s,\-.]+$/u';
        $validationCareerId = 'required|exists:careers,id';
        
        $areaId = $this->route('id'); // Asegúrate de pasar el ID en la ruta
        
        if ($areaId) {
            $validationName = [
                'required',
                'string',
                'max:50',
                'regex:/^[\pL\s,\-.]+$/u',
                Rule::unique('areas')->ignore($areaId)->where(function ($query) use ($careerId) {
                    return $query->where('career_id', $careerId);
                }),
            ];
        
            $validationDescription = 'string|max:255|regex:/^[\pL\s,\-.]+$/u';
            $validationCareerId = 'required|exists:careers,id';
        }
        
        return [
            'name' => $validationName,
            'description' => $validationDescription,
            'career_id' => $validationCareerId, // Asegúrate de incluir career_id en el array de validación
        ];
        
    }

    /**
     * Mensajes personalizados para las reglas de validación.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del área es obligatorio.',
            'name.unique' => 'Ya existe un área con este nombre en la carrera seleccionada.',
            'name.max' => 'El nombre del área no puede tener más de 50 caracteres.',
            'name.regex' => 'El nombre del área solo puede contener letras, espacios, comas, guiones y puntos.',
            'description.max' => 'La descripción no puede tener más de 255 caracteres.',
            'description.regex' => 'La descripción solo puede contener letras, espacios, comas, guiones y puntos.',
            'description.regex' => 'Solo debe contener letras',
            'career_id.required' => 'La carrera es obligatoria.',
            'career_id.exists' => 'La carrera seleccionada no existe.',
        ];
    }
}
