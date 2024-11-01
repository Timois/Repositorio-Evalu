<?php

namespace App\Http\Requests;

use App\Models\AcademicManagement;
use App\Models\AcademicManagementCareer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

use function Laravel\Prompts\error;

class ValidationAcademicManagementPeriod extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function buscar()
    {

        $id = $this->academic_management_career_id;
        $res = AcademicManagementCareer::find($id);
        if (!$res)
            return null;
        $mangement_id = $res->academic_management_id;
        $management = AcademicManagement::find($mangement_id);
        return $management;
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $management = $this->buscar();
        $initial_date = $this->initial_date;
        if ($management)
            return [
                'initial_date' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:' . $management->end_date, 'after_or_equal:' . $management->initial_date],
                'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after:' . $initial_date, 'before_or_equal:' . $management->end_date],
                'academic_management_career_id' => ['required', 'exists:academic_management_career,id'],
                'period_id' => ['required', 'exists:periods,id'],
            ];

        return [
            'academic_management_career_id' => ['required', 'exists:academic_management_career,id'],
        ];
    }

    public function messages()
    {
        $management = $this->buscar();
        if ($management)
            return [
                'initial_date.required' => 'La fecha inicio es obligatorio',
                'initial_date.date' => 'La fecha de inicio tiene que ser una fecha',
                'initial_date.date_format' => 'La fecha debe estar en formato:Y-m-d.',
                'initial_date.before_or_equal' => 'La fecha inicio tiene que estar en el rango de: ' . $management->initial_date . ' - ' . $management->end_date,
                'initial_date.after_or_equal' => 'La fecha inicio tiene que estar en el rango de: ' . $management->initial_date . ' - ' . $management->end_date,
                'end_date.required' => 'La fecha fin es obligatorio',
                'end_date.date' => 'La fecha fin tiene que ser una fecha',
                'end_date.date_format' => 'La fecha debe estar en formato:Y-m-d.',
                'end_date.after' => 'La fecha fin no puede ser antes de la fecha de inicio de gestion academica',
                'end_date.before_or_equal' => 'La fecha fin tiene que estar en el rango de: ' . $management->initial_date . ' - ' . $management->end_date,
                'academic_management_career_id.required' => 'El id de la gestion academica de la carrera es obligatorio',
                'academic_management_career_id.exists' => 'No existe el id de la gestion academica de la carrera',
                'period.required' => 'El id del periodo es obligatorio',
                'period.exists' => 'No eciste el id del periodo'
            ];

        return [
            'academic_management_career_id.required' => 'El id de la gestion academica de la carrera es obligatorio',
            'academic_management_career_id.exists' => 'No existe el id de la gestion academica de la carrera',
        ];
    }
}
