<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidationEvaluation extends FormRequest
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
        $rules = [];

    // Validar el campo "title" si está presente
    $rules['title'] = 'sometimes|required|string|max:255|regex:/^[\pL\s,\-.\d]+$/u';

    // Validar el campo "description" si está presente
    $rules['description'] = 'sometimes|string|max:255|regex:/^[\pL\s,\-.\d]+$/u';

    // Validar el campo "number_questions" si está presente
    $rules['number_questions'] = 'sometimes|required|numeric|min:0';

    // Validar el campo "total_score" si está presente
    $rules['total_score'] = 'sometimes|required|numeric|min:0';

    // Validar el campo "is_random" si está presente
    $rules['is_random'] = 'sometimes|required|boolean';

    // Validar el campo "status" si está presente
    $rules['status'] = 'sometimes|required|in:activo,inactivo';

    // Validar el campo "type" si está presente
    $rules['type'] = 'sometimes|required|in:web,ocr,app';

    return $rules;
    }

    protected function prepareForValidation()
    {
        if ($this->has('title')) {
            $this->merge([
                'title' => strtoupper($this->title)
            ]);
        }
        if ($this->has('description')) {
            $this->merge([
                'description' => strtolower($this->description)
            ]);
        }
    }

    public function messages()
    {
        return [
            'title.required' => 'El campo título es obligatorio.',
            'title.string' => 'El campo título debe ser una cadena de texto.',
            'title.max' => 'La longitud máxima del título es de 255 caracteres.',
            'title.regex' => 'El título debe contener solo letras y espacios.',
            'description.string' => 'El campo descripción debe ser una cadena de texto.',
            'description.max' => 'La longitud máxima de la descripción es de 255 caracteres.',
            'description.regex' => 'La descripción debe contener solo letras y espacios.',
            'number_questions.required' => 'El campo número de preguntas es obligatorio.',
            'number_questions.numeric' => 'El campo número de preguntas debe ser un número.',            
            'number_questions.min' => 'El campo número de preguntas debe ser mayor o igual a 0.',
            'total_score.required' => 'El campo puntaje total es obligatorio.',
            'total_score.numeric' => 'El campo puntaje total debe ser un número.',
            'total_score.min' => 'El campo puntaje total debe ser mayor o igual a 0.',
            'is_random.required' => 'El campo aleatorio es obligatorio.',
            'is_random.boolean' => 'El campo aleatorio debe ser "false" o "true".',
            'status.required' => 'El campo estado es obligatorio.',
            'status.in' => 'El campo estado debe ser "activo" o "inactivo".',
            'type.required' => 'El campo tipo es obligatorio.',
            'type.in' => 'El campo tipo debe ser "web", "ocr" o "app".',
        ];
    }
}
