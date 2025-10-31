<?php

namespace App\Http\Requests;

use App\Models\AcademicManagement;
use App\Models\AcademicManagementCareer;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;


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
        $id = $this->route("id");
        if ($id)
            return [
                'initial_date' => ['required_if:id,null', 'date', 'date_format:Y-m-d H:i:s', 'before_or_equal:' . $management->end_date, 'after_or_equal:' . $management->initial_date],
                'end_date' => [
                    'required',
                    'date',
                    'date_format:Y-m-d H:i:s',
                    'after:' . $initial_date,
                    'before_or_equal:' . Carbon::parse($management->end_date)->addDay()->format('Y-m-d H:i:s')
                ],
                'academic_management_career_id' => ['required', 'exists:academic_management_career,id'],
                'period_id' => ['required', 'exists:periods,id'],
            ];

        return [
            'initial_date' => ['required', 'date', 'date_format:Y-m-d H:i:s', 'before_or_equal:' . $management->end_date, 'after_or_equal:' . $management->initial_date],
            'end_date' => [
                'required',
                'date',
                'date_format:Y-m-d H:i:s',
                'after:' . $initial_date,
                'before_or_equal:' . Carbon::parse($management->end_date)->addDay()->format('Y-m-d H:i:s')
            ],
            'academic_management_career_id' => ['required', 'exists:academic_management_career,id'],
        ];
    }

    public function messages()
    {
        $management = $this->buscar();
        if ($management)
            return [
                'initial_date.required' => 'La fecha inicio es obligatorio',
                'initial_date.date' => 'La fecha de inicio debe ser una fecha valida',
                'initial_date.date_format' => 'La fecha debe estar en formato:Y-m-d. H:i:s',
                'initial_date.before_or_equal' => 'La fecha inicio tiene que estar en el rango de: ' . $management->initial_date . ' - ' . $management->end_date,
                'initial_date.after_or_equal' => 'La fecha inicio tiene que estar en el rango de: ' . $management->initial_date . ' - ' . $management->end_date,
                'end_date.required' => 'La fecha fin es obligatorio',
                'end_date.date' => 'La fecha fin tiene que ser una fecha',
                'end_date.date_format' => 'La fecha debe estar en formato:Y-m-d. H:i:s',
                'end_date.after' => 'La fecha fin no puede ser antes de la fecha de inicio de gestion academica',
                'end_date.before_or_equal' => 'La fecha fin tiene que estar en el rango de: ' . $management->initial_date . ' - ' . $management->end_date,
                'academic_management_career_id.required' => 'El id de la gestion academica de la carrera es obligatorio',
                'academic_management_career_id.exists' => 'No existe el id de la gestion academica de la carrera',
                'period.required' => 'El id del periodo es obligatorio',
                'period.exists' => 'No existe el id del periodo'
            ];

        return [
            'academic_management_career_id.required' => 'El id de la gestion academica de la carrera es obligatorio',
            'academic_management_career_id.exists' => 'No existe el id de la gestion academica de la carrera',
        ];
    }
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $careerId = $this->academic_management_career_id;
            $periodId = $this->period_id;
            $initialDate = Carbon::parse($this->initial_date);
            $endDate = Carbon::parse($this->end_date);
            $id = $this->route('id'); // para excluir el registro actual si estás editando

            // Buscar si existe otro periodo que se solape
            $exists = \App\Models\AcademicManagementPeriod::where('academic_management_career_id', $careerId)
                ->where('period_id', $periodId)
                ->where(function ($query) use ($initialDate, $endDate) {
                    $query->whereBetween('initial_date', [$initialDate, $endDate])
                        ->orWhereBetween('end_date', [$initialDate, $endDate])
                        ->orWhere(function ($q) use ($initialDate, $endDate) {
                            $q->where('initial_date', '<=', $initialDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })
                ->when($id, fn($q) => $q->where('id', '!=', $id)) // excluir el actual si estás editando
                ->exists();

            if ($exists) {
                $validator->errors()->add('period_id', 'El periodo ya existe en un rango de fechas que se solapa para esta carrera.');
            }
        });
    }
}
