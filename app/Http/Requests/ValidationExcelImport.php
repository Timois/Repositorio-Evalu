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
        return [
            'file_name' => ['required','file','mimes:xlsx,xls,csv','max:10240', // 10MB
                function ($attribute, $value, $fail) {
                    // Validate file is not empty
                    // if ($value->getSize() === 0) {
                    //     $fail('El archivo no puede estar vacío');
                    // }

                    // Check for duplicate file content
                    // $fileHash = hash_file('sha256', $value->getRealPath());
                    // $existingImport = ExcelImports::where('file_hash', $fileHash)->exists();

                    // if ($existingImport) {
                    //     $fail('Este archivo ya ha sido importado previamente');
                    // }

                    // Additional custom validation for Excel format
                    try {
                        $tempImport = new QuestionBankImport(null);
                        $data = Excel::toArray($tempImport, $value);
                        $validationMessages = $tempImport->validateFormat($data);

                        if (!empty($validationMessages)) {
                            foreach ($validationMessages as $message) {
                                $fail($message);
                            }
                        }
                    } catch (\Exception $e) {
                        $fail('Error al validar el formato del archivo: ' . $e->getMessage());
                    }
                },
            ],
            'status' => 'required|in:completado,error',
        ];
    }

    public function messages(): array
    {
        return [
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
