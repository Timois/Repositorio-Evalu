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
        $validationFileName = 'required|file|mimes:xlsx,xls,csv|max:2048';
        $validationOriginalName = 'required|string|max:255|regex:/^[\pL\s,\-.]+$/u|unique:excel_imports,original_name';
        $validationStatus = 'required|in:pendiente,completado,procesando,error';

        $excelId = $this->route('id');
        if ($excelId) {
            // Validaciones específicas para edición
            $validationFileName = 'nullable|file|mimes:xlsx,xls,csv|max:2048';
            $validationOriginalName = 'required|string|max:255|regex:/^[\pL\s,\-.]+$/u|unique:excel_imports,original_name,' . $excelId;
            $validationStatus = 'required|in:pendiente,completado,procesando,error';
        }

        return [
            'file_name' => $validationFileName,
            'original_name' => $validationOriginalName,
            'status' => $validationStatus,
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'file_name.required' => 'El archivo es obligatorio.',
            'file_name.file' => 'El archivo debe ser válido.',
            'file_name.mimes' => 'Solo se permiten archivos de tipo xlsx, xls o csv.',
            'file_name.max' => 'El tamaño máximo permitido del archivo es de 2 MB.',
            'original_name.required' => 'El nombre original es obligatorio.',
            'original_name.string' => 'El nombre debe ser un texto.',
            'original_name.max' => 'El nombre no puede exceder los 255 caracteres.',
            'original_name.regex' => 'Solo debe contener letras, espacios, comas, guiones o puntos.',
            'original_name.unique' => 'El nombre original ya existe.',
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
