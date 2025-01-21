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
        $validationQuestion = 'required||string|max:255|regex:/^[\pL\s,\-.]+$/u|unique:question_bank,name';
        $validationDescription = 'string|max:255|regex:/^[\pL\s,\-.]+$/u';
        $validateImage = 'required|image|mimes:jpeg,png,jpg,webp,svg|max:2048';
        $validateTotalWeight = 'required|numeric|min:0';
        $validateType = 'required|in:multiple,una opcion';
        $validateStatus = 'required|in:activo,inactivo';
        $bankQuestion = $this->route("id");
        if ($bankQuestion) {
            $validationQuestion = 'required||string|max:255|regex:/^[\pL\s,\-.]+$/u|unique:question_bank,name';
            $validationDescription = 'string|max:255|regex:/^[\pL\s,\-.]+$/u';
            $validateImage = 'required|image|mimes:jpeg,png,jpg,webp,svg|max:2048';
            $validateTotalWeight = 'required|numeric|min:0';
            $validateType = 'required|in:multiple,una opcion';
            $validateStatus = 'required|in:activo,inactivo';
            $bankQuestion = $this->route("id");
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
        if($this->has('question')) {
            $this->merge([
                'question' => strtolower($this->question)
            ]);
        }
        if($this->has('description')) {
            $this->merge([
                'description' => strtolower($this->description)
            ]);
        }
    }
    public function messages()
    {
        $question = $this->request->filter('question'); 
        $bankQuestion = DB::table('question_bank')->where('question', '=', $question)->first();
        return [
            'question.required' => 'La pregunta es obligatoria.',
            'question.regex' => 'Solo debe contener letras.',
            'question.unique' => 'La pregunta ya existe. ID de la pregunta existente:' . (($bankQuestion) ? $bankQuestion->id:0),
            'description.regex' => 'Solo debe contener letras.',
            'image.required' => 'Debe subir una imagen para el campo path.',
            'image.image' => 'El archivo subido debe ser una imagen válida.',
            'image.mimes' => 'La imagen debe estar en uno de los siguientes formatos: jpeg, png, jpg, webp, svg.',
            'image.max' => 'La imagen no debe superar los 2 MB.',
            'total_weight.required' => 'El peso total es obligatorio.',
            'total_weight.numeric' => 'El peso total debe ser un valor numérico.',
            'total_weight.min' => 'El peso total debe ser mayor o igual a 0.',
            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'El tipo debe ser "multiple" o "una opcion".',
            'status.required' => 'El estado es obligatorio.',            
            'status.in' => 'El estado debe ser "activo" o "inactivo".',
        ];
    }
}