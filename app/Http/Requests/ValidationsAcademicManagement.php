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
        $fecha = Carbon::parse($initial_date);
        $year= $fecha->year;
        return [
            'year' => ['numeric', 'required_if:id,null' , 'max:4','unique:academic_management,year,' . ($id ?? 'NULL'),'date_equals:'.$year],
            'initial_date' => ['required_if:id,null', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required_if:id,null', 'date', 'date_format:Y-m-d', 'after:' . $initial_date],
        ];
    }

    public function messages()
    {
        return [
            'year.numeric' => 'La gestion es un numero.',
            'year.max' => 'El año debe ser de 4 digitos',
            'year.required' => 'El año es obligatorio.',
            'year.date_equals'=> 'El año debe ser igual al año de fecha inicio',
            'year.unique' => 'El año ya ha sido registrado. Por favor, elige otro.',
            'initial_date.required' => 'La fecha inicio es obligatorio',
            'initial_date.date' => 'La fecha de inicio tiene que ser una fecha',
            'initial_date.date_format' => 'La fecha debe estar en formato:Y-m-d.',
            'initial_date.after_or_equal' => 'La fecha inicio no puede ser antes de hoy.',
            'end_date.required' => 'La fecha fin es obligatorio',
            'end_date.date' => 'La fecha fin tiene que ser una fecha',
            'end_date.date_format' => 'La fecha debe estar en formato:Y-m-d.',
            'end_date.after' => 'La fecha fin no puede ser antes de la fecha de inicio'
        ];
    }
}
