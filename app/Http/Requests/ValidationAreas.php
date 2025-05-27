<?php

namespace App\Http\Requests;

use App\Models\Career;
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
        $areaId = $this->route('id');

        // Verificar si la carrera existe y si su tipo es "carrera"
        $career = Career::find($careerId);
        $isAllowedCareer = $career && $career->type === 'carrera';

        $validationName = [
            'required',
            'string',
            'max:50',
            'regex:/^[\pL\s,\-.]+$/u',
            Rule::unique('areas')->where(fn($query) => $query->where('career_id', $careerId)),
        ];

        $validationDescription = 'string|max:255|regex:/^[\pL\s,\-.]+$/u';
        $validationCareerId = [
            'required',
            'exists:careers,id',
            function ($attribute, $value, $fail) {
                $career = Career::find($value);
                if (!$career || $career->type !== 'carrera') {
                    $fail('Solo se pueden asignar áreas a unidades de tipo "carrera".');
                }
            },
        ];

        if ($areaId) {
            $validationName = [
                'required',
                'string',
                'max:50',
                'regex:/^[\pL\s,\-.]+$/u',
                Rule::unique('areas')->ignore($areaId)->where(fn($query) => $query->where('career_id', $careerId)),
            ];
        }

        return [
            'name' => $validationName,
            'description' => $validationDescription,
            'career_id' => $validationCareerId,
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $originalName = $this->input('name');
            $normalized = $this->normalizeText($originalName);

            $this->merge([
                'name' => $normalized,
                'original_name' => $originalName, // para guardar el nombre original si lo necesitas
            ]);
        }
    }

    /**
     * Elimina acentos y convierte a minúsculas para comparación.
     */
    protected function normalizeText(string $text): string
    {
        return strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text));
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
            'career_id.required' => 'La carrera es obligatoria.',
            'career_id.exists' => 'La carrera seleccionada no existe.',
        ];
    }
}
