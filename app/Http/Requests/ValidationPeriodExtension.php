<?php

namespace App\Http\Requests;

use App\Models\AcademicManagementPeriod;
use Illuminate\Foundation\Http\FormRequest;

class ValidationPeriodExtension extends FormRequest
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
        $initial_date = $this->initial_date;
        return [
            'initial_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:' . $initial_date],
            'academic_management_period_id' => ['required', 'exists:academic_management,id']
        ];
    }
    public function messages()
    {
        return[
            'initial_date.required' => 'La fecha inicio es obligatorio',
            'initial_date.date_format' => 'La fecha debe estar en formato:Y-m-d.',
            'initial_date.after_or_equal' => 'La fecha inicio no puede ser antes de hoy.',
            'end_date.required' => 'La fecha fin es obligatorio',
            'end_date.date_format' => 'La fecha debe estar en formato:Y-m-d.',
            'end_date.after' => 'La fecha fin no puede ser antes de la fecha de inicio',
            'academic_management_period_id.required' => 'El id del periodo de la gestion academica es obligatorio',
            'academic_management_period_id.exists' => 'No existe el id del periodo de la gestion academica'
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(
            function ($validator) {
                $academic_management_period_id = $this->academic_management_period_id;
                $academic_management_period = AcademicManagementPeriod::find($academic_management_period_id);

                if ($academic_management_period->end_date >= $this->date_extension) {
                    $validator->errors()->add('date_extension', 'la extension tiene que ser despues de la fecha fin del periodo de la gestion academica');
                }
            }
        );
    }
}
