<?php

namespace App\Http\Requests;

use App\Models\AcademicManagement;
use Illuminate\Foundation\Http\FormRequest;

class ValidationManagementExtension extends FormRequest
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
        return [
            'date_extension' => ['required', 'date','date_format:Y-m-d'],
            'academic_management_id' => ['required', 'exists:academic_management,id']
        ];
    }
    public function messages()
    {
        return[
            'date_extension.required' => 'La fecha de extension es obligatorio',
            'date_extension.date' => 'la fecha extension debe ser una fecha',
            'date_extension.date_format' => 'La fecha debe estar en formato:Y-m-d.',
            'academic_management_id.required' => 'El id de la gestion academica es obligatorio',
            'academic_management_id.exists' => 'No existe el id de la gestion academica'
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(
            function ($validator) {
                $academic_management_id = $this->academic_management_id;
                $academic_management = AcademicManagement::find($academic_management_id);

                if ($academic_management->end_date >= $this->date_extension) {
                    $validator->errors()->add('date_extension', 'la extension tiene que ser despues de la fecha fin de gestion');
                }
            }
        );
    }
}
