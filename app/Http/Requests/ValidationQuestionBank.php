<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class ValidationQuestionBank extends FormRequest
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
        // Regla base para la validación de la pregunta
        $validationQuestion = 'required|string|max:255|regex:/^[\pL\s,\-.\d\(\)\[\]\+\*\/=\^_°√\?\¿]+$/u|unique:bank_questions,question,NULL,id,area_id,' . $this->input('area_id');

        // Si se está editando una pregunta, excluir su propio ID de la validación de unicidad
        if ($this->route("id")) {
            $validationQuestion = 'required|string|max:255|regex:/^[\pL\s,\-.\d\(\)\[\]\+\*\/=\^_°√\?\¿]+$/u|unique:bank_questions,question,' . $this->route("id") . ',id,area_id,' . $this->input('area_id');
        }

        return [
            'question' => $validationQuestion,
            'description' => 'nullable|string|max:255|regex:/^[\pL\s,\-.]+$/u',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'question_type' => 'required|in:text,imagen',
            'dificulty' => 'required|in:facil,medio,dificil',
            'type' => 'required|in:multiple,una opcion',
            'status' => 'required|in:activo,inactivo',
            'excel_import_id' => 'nullable|integer', // Permitir que sea nulo
            'area_id' => 'required|exists:areas,id',  // Validar que el área exista
        ];
    }

    /**
     * Modificar los datos antes de la validación.
     */
    protected function prepareForValidation()
    {
        // Convertir campos a minúsculas
        $fieldsToLowercase = ['question', 'description'];

        foreach ($fieldsToLowercase as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => strtolower($this->input($field))
                ]);
            }
        }

        // Asegurarse de que area_id esté presente en la solicitud
        if (!$this->has('area_id')) {
            $this->merge([
                'area_id' => $this->route('area_id') // O cualquier otra lógica para obtener el area_id
            ]);
        }
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages()
    {
        // Obtener la pregunta y el área actual
        $question = $this->input('question');
        $areaId = $this->input('area_id');

        // Buscar si ya existe una pregunta igual en la misma área
        $bankQuestion = DB::table('bank_questions')
            ->where('question', $question)
            ->where('area_id', $areaId)
            ->first();

        return [
            'question.required' => 'La pregunta es obligatoria.',
            'question.regex' => 'Solo debe contener letras o símbolos matemáticos.',
            'question.unique' => 'La pregunta ya existe en esta área. ID de la pregunta existente: ' . ($bankQuestion ? $bankQuestion->id : 'N/A'),
            'description.regex' => 'Solo debe contener letras.',
            'image.image' => 'El archivo subido debe ser una imagen válida.',
            'image.mimes' => 'La imagen debe estar en uno de los siguientes formatos: jpeg, png, jpg, webp, svg.',
            'image.max' => 'La imagen no debe superar los 2 MB.',
            'question_type.required' => 'El tipo de pregunta es obligatorio.',
            'question_type.in' => 'El tipo de pregunta debe ser "text" o "imagen".',
            'dificulty.required' => 'La dificultad es obligatoria.',
            'dificulty.in' => 'La dificultad debe ser "facil", "medio" o "dificil".',
            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'El tipo debe ser "multiple" o "una opcion".',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser "activo" o "inactivo".',
            'area_id.required' => 'El área es obligatoria.',
            'area_id.exists' => 'El área no existe.',
        ];
    }
}