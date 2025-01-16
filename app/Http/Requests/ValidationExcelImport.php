<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

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
        $excelId = $this->route('id'); // Obtener el id del archivo si estamos editando

        $rules = [
            'file_name' => 'required|file|mimes:xlsx,xls,csv|max:2048',
            'status' => 'required|in:procesando,error',
        ];

        if ($excelId) {
            // Validaciones específicas para la edición
            $rules['file_name'] = 'nullable|file|mimes:xlsx,xls,csv|max:2048';
            $rules['file_name'] .= '|unique:excel_imports,original_name,' . $excelId; // Excluir el archivo actual de la validación de unicidad
        }

        return $rules;
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'El archivo es obligatorio.',
            'file_name.file' => 'El archivo debe ser válido.',
            'file_name.mimes' => 'Solo se permiten archivos de tipo xlsx, xls o csv.',
            'file_name.max' => 'El tamaño máximo permitido del archivo es de 2 MB.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser uno de los siguientes valores: pendiente, completado, procesando o error.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 422)); // Respuesta con código 422: Unprocessable Entity
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'original_name' => strtolower($this->original_name),
        ]);
    }
}
