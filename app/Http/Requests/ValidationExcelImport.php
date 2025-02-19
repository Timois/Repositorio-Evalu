<?php

namespace App\Http\Requests;

use App\Imports\QuestionBankImport;
use App\Models\ExcelImports;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Maatwebsite\Excel\Facades\Excel;

class ValidationExcelImport extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Permitir el acceso al formulario
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $validationFile = $this->file('file_name');
        $validationCareer = 'required|max:255|regex:/[a-zA-Zñ]+/';
        $validationSigla = 'required|string|max:10|regex:/[a-zA-Zñ]+/';
        $validationStatus = 'required|in:completado,error';
        if ($validationFile) {
            $validationFile = $validationFile->getClientOriginalName();
            $validationFile = 'required|file|mimes:xlsx,xls,csv|max:10000';
            $validationCareer = 'required|max:255|regex:/[a-zA-Zñ]+/';
            $validationSigla = 'required|string|max:10|regex:/[a-zA-Zñ]+/';
            $validationStatus = 'required|in:completado,error';
        }
        return [
            'career' => $validationCareer,
            'sigla' => $validationSigla,
            'file_name' => 'required|file|mimes:xlsx,xls,csv|max:10000',
            'status' => $validationStatus,
        ];
    }   

    public function messages(): array
    {
        return [
            'career.required' => 'La carrera es obligatoria.',
            'career.regex' => 'Solo debe contener letras.',
            'sigla.required' => 'La sigla es obligatoria.',
            'sigla.regex' => 'Solo debe contener letras.',
            'file_name.required' => 'El archivo es obligatorio.',
            'file_name.file' => 'El archivo debe ser válido.',
            'file_name.mimes' => 'Solo se permiten archivos de tipo xlsx, xls o csv.',
            'file_name.max' => 'El tamaño máximo permitido del archivo es de 10 MB.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser uno de: completado o error.',
        ];
    }
    protected function prepareForValidation(): void
    {
        $this->merge([
            'file_name' => strtolower($this->original_name),
        ]);
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 422));
    }
    
}
