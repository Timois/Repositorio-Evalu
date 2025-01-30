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
            $validationQuestion = 'required|string|max:255|regex:/^[\pL\s,\-.\d\(\)\[\]\+\*\/=\^_°√\?\¿]+$/u|unique:bank_questions,question';
            $validationDescription = 'string|max:255|regex:/^[\pL\s,\-.]+$/u';
            $validateImage = 'image|mimes:jpeg,png,jpg,webp,svg|max:2048';
            $validateTotalWeight = 'required|numeric|min:0';
            $validateType = 'required|in:multiple,una opcion';
            $validateStatus = 'required|in:activo,inactivo';
            $bankQuestion = $this->route("id");
            if ($bankQuestion) {
                $validationQuestion = 'required|string|max:255|regex:/^[\pL\s,\-.\d\(\)\[\]\+\*\/=\^_°√\?\¿]+$/u|unique:bank_questions,question,' . $bankQuestion;
                $validationDescription = 'string|max:255|regex:/^[\pL\s,\-.]+$/u';
                $validateImage = 'required|image|mimes:jpeg,png,jpg,webp,svg|max:2048';
                $validateTotalWeight = 'required|numeric|min:0';
                $validateType = 'required|in:multiple,una opcion';
                $validateStatus = 'required|in:activo,inactivo';
            }
            return [
                'question' => $validationQuestion,
                'description' => $validationDescription,            
                'image' => $validateImage,
                'total_weight' => $validateTotalWeight,
                'type' => $validateType,
                'status' => $validateStatus
            ];
        }
        protected function prepareForValidation()
        {
            $fieldsToLowercase = ['question', 'description'];
            
            foreach ($fieldsToLowercase as $field) {
                if ($this->has($field)) {
                    $this->merge([
                        $field => strtolower($this->input($field))
                    ]);
                }
            }
        }
        public function messages()
        {
            $question = $this->request->filter('question'); 
            $bankQuestion = DB::table('bank_questions')->where('question', '=', $question)->first();
            return [
                'question.required' => 'La pregunta es obligatoria.',
                'question.regex' => 'Solo debe contener letras o simbolos matematicos.',
                'question.unique' => 'La pregunta ya existe. ID de la pregunta existente:' . (($bankQuestion) ? $bankQuestion->id:0),
                'description.regex' => 'Solo debe contener letras.',
                'image.image' => 'El archivo subido debe ser una imagen válida.',
                'image.mimes' => 'La imagen debe estar en uno de los siguientes formatos: jpeg, png, jpg, webp, svg.',
                'image.max' => 'La imagen no debe superar los 2 MB.',
                'total_weight.required' => 'La nota total es obligatorio.',
                'total_weight.numeric' => 'La nota total debe ser un valor numérico.',
                'total_weight.min' => 'La nota total debe ser mayor o igual a 0.',
                'type.required' => 'El tipo es obligatorio.',
                'type.in' => 'El tipo debe ser "multiple" o "una opcion".',
                'status.required' => 'El estado es obligatorio.',            
                'status.in' => 'El estado debe ser "activo" o "inactivo".',
            ];
        }
}