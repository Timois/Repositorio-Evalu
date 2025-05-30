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
        $validationAreaId = 'required|exists:areas,id';
        $validationPeriodId = 'required|exists:academic_management_period,id';
        $validationStatus = 'required|in:completado,error';
        $validationDescription = 'string|max:255|regex:/^[a-zA-Z0-9\s,.\-\/]+$/';
        if ($validationFile) {
            $validationFile = $validationFile->getClientOriginalName();
            $validationFile = 'required|file|mimes:xlsx,xls,csv|max:10000';
            $validationStatus = 'required|in:completado,error';
            $validationDescription = 'string|max:255|regex:/^[a-zA-Z0-9\s,.\-\/]+$/';

        }
        return [
            'file_name' => 'required|file|mimes:xlsx,xls,csv|max:10000',
            'area_id' => $validationAreaId,
            'academic_management_period_id' => $validationPeriodId,
            'description' => $validationDescription,
            'status' => $validationStatus,
        ];
    }   

    public function messages(): array
    {
        return [
            'file_name.required' => 'El archivo es obligatorio.',
            'file_name.file' => 'El archivo debe ser válido.',
            'file_name.mimes' => 'Solo se permiten archivos de tipo xlsx, xls o csv.',
            'file_name.max' => 'El tamaño máximo permitido del archivo es de 10 MB.',
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede exceder los 255 caracteres.',
            'description.regex' => 'La descripción solo puede contener letras, números, espacios y algunos caracteres especiales.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser uno de: completado o error.',
            'area_id.required' => 'El área es obligatoria.',
            'area_id.exists' => 'El área seleccionado no existe.',
            'academic_management_period_id.required' => 'El periodo académico es obligatorio.',
            'academic_management_period_id.exists' => 'El periodo académico seleccionado no existe.',
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
