<?php

namespace App\Http\Requests;

use App\Models\AcademicManagementCareer;
use App\Models\Career;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidationAssignManagements extends FormRequest
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
            'career_id' => [
                'required',
                Rule::exists('careers', 'id')->where('type', 'carrera') // Solo carreras
            ],
            'academic_management_id' => 'required|exists:academic_management,id',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $career_id = $this->career_id;
            $academic_management_id = $this->academic_management_id;
            $id = $this->route('academic_management_career'); // Obtiene el ID del registro que se está editando

            // Obtener la carrera asociada
            $career = Career::find($career_id);

            // Verificar si la carrera existe
            if (!$career) {
                $validator->errors()->add('career_id', 'La carrera seleccionada no existe.');
                return;
            }

            // Verificar si el tipo de la carrera es "carrera"
            if ($career->type !== 'carrera') {
                $validator->errors()->add('career_id', 'Solo se puede asignar una gestión a una carrera.');
                return;
            }

            // Verificar si la asignación ya existe para otra carrera
            $assign = AcademicManagementCareer::where('career_id', $career_id)
                ->where('academic_management_id', $academic_management_id)
                ->where('id', '!=', $id) // Excluye el registro actual en la verificación
                ->first();

            if ($assign) {
                $validator->errors()->add('academic_management_id', 'La gestión ya ha sido asignada a esta carrera.');
            }
        });
    }

    public function messages()
    {
        return [
            'career_id.required' => 'el id de la carrera es requerido',
            'career_id.exists' => 'Solo se puede asignar gestión a carreras. o el id de la unidad no existe',
            'academic_management_id.required' => 'el id de la gestion academica es requerido',
            'academic_management_id.exists' => 'el id de la gestion academica no existe',
        ];
    }
}
