<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class ValidationsAcademicManagement extends FormRequest
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
    public function rules()
    {
        $id = $this->route("id");
        $initial_date = $this->initial_date;

        // Si hay una fecha inicial en la request, úsala para validar
        // Si no, obtén la fecha inicial existente de la base de datos
        if (!$initial_date && $id) {
            $academicManagement = \App\Models\AcademicManagement::find($id);
            $initial_date = $academicManagement->initial_date;
        }

        // Solo parsear la fecha si existe
        $year = null;
        if ($initial_date) {
            $fecha = Carbon::parse($initial_date);
            $year = $fecha->year;
        }

        $rules = [
            'year' => ['numeric', 'digits:4', 'unique:academic_management,year,' . ($id ?? 'NULL')],
            'end_date' => ['required', 'date', 'date_format:Y-m-d']
        ];

        // Si es una creación nueva
        if (!$id) {
            $rules['initial_date'] = [
                'required',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:today'  // Solo aplicar en creación nueva
            ];
            $rules['year'][] = 'required';
        }
        // Si es una edición y se está modificando la fecha inicial
        elseif ($this->has('initial_date')) {
            $rules['initial_date'] = [
                'required',
                'date',
                'date_format:Y-m-d'
                // No incluimos after_or_equal:today en ediciones
            ];
            if ($year) {
                $rules['year'][] = 'date_equals:' . $year;
            }
        }

        // Siempre validar que end_date sea después de initial_date
        if ($initial_date) {
            $rules['end_date'][] = 'after:' . $initial_date;
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'year.numeric' => 'La gestión es un número.',
            'year.digits' => 'El año debe ser de 4 dígitos',
            'year.required' => 'El año es obligatorio.',
            'year.unique' => 'El año ya ha sido registrado. Por favor, elige otro.',
            'initial_date.required' => 'La fecha inicio es obligatoria',
            'initial_date.date' => 'La fecha de inicio tiene que ser una fecha',
            'initial_date.date_format' => 'La fecha debe estar en formato Y-m-d.',
            'initial_date.after_or_equal' => 'La fecha inicio no puede ser antes de hoy.',
            'end_date.required' => 'La fecha fin es obligatoria',
            'end_date.date' => 'La fecha fin tiene que ser una fecha',
            'end_date.date_format' => 'La fecha debe estar en formato Y-m-d.',
            'end_date.after' => 'La fecha fin no puede ser antes de la fecha de inicio'
        ];
    }
}
