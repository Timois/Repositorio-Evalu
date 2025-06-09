<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidationGroup extends FormRequest
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
        $validationEvluationId = 'required|exists:evaluations,id';
        $validationLaboratoryIds = 'required|array|min:1';
        $validationLaboratoryIdsElements = 'exists:laboratories,id';
        $validationName = 'required|string|max:20';
        $validationStartTime = 'required|date_format:H:i';
        $validationEndTime = 'required|date_format:H:i|after:start_time';

        $groupId = $this->route('id');

        if ($groupId) {
            $validationEvluationId = 'sometimes|exists:evaluations,id';
            $validationLaboratoryIds = 'sometimes|array|min:1';
            $validationLaboratoryIdsElements = 'exists:laboratories,id';
            $validationName = 'sometimes|string|max:20';
            $validationStartTime = 'sometimes|date_format:H:i';
            $validationEndTime = 'sometimes|date_format:H:i|after:start_time';
        }

        return [
            'evaluation_id' => $validationEvluationId,
            'laboratory_ids' => $validationLaboratoryIds,
            'laboratory_ids.*' => $validationLaboratoryIdsElements,
            'name' => $validationName,
            'start_time' => $validationStartTime,
            'end_time' => $validationEndTime,
        ];
    }


    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Obtener evaluación
            $evaluation = \App\Models\Evaluation::find($this->evaluation_id);

            if (!$evaluation) {
                return; // ya se validó exists, pero por si acaso
            }

            // Obtener la fecha del examen
            $examDate = \Carbon\Carbon::parse($evaluation->date_of_realization);

            // Construir fecha completa con hora de inicio y fin
            $startTime = \Carbon\Carbon::parse($evaluation->date_of_realization . ' ' . $this->start_time);
            $endTime = \Carbon\Carbon::parse($evaluation->date_of_realization . ' ' . $this->end_time);

            // Validar que ambos sean el mismo día que date_of_realization (opcional porque ya estamos forzando la fecha)
            if (!$startTime->isSameDay($examDate)) {
                $validator->errors()->add('start_time', 'La hora de inicio debe corresponder a la fecha del examen: ' . $examDate->format('Y-m-d'));
            }

            if (!$endTime->isSameDay($examDate)) {
                $validator->errors()->add('end_time', 'La hora de fin debe corresponder a la fecha del examen: ' . $examDate->format('Y-m-d'));
            }

            // Validar que la duración no supere el tiempo del examen
            $durationMinutes = $startTime->diffInMinutes($endTime);

            if ($durationMinutes > $evaluation->time) {
                $validator->errors()->add('end_time', 'La duración del grupo no puede exceder la duración del examen (' . $evaluation->time . ' minutos).');
            }
        });
    }


    protected function prepareForValidation()
    {
        $this->merge([
            'name' => strtolower(trim($this->input('name'))),
        ]);
    }
    public function messages()
    {
        return [
            'evaluation_id.required' => 'El campo evaluación es obligatorio.',
            'evaluation_id.exists' => 'La evaluación seleccionada no existe.',

            'laboratory_ids.required' => 'Debe seleccionar al menos un laboratorio.',
            'laboratory_ids.array' => 'El campo laboratorios debe ser un arreglo de laboratorios válidos.',
            'laboratory_ids.min' => 'Debe seleccionar al menos un laboratorio.',
            'laboratory_ids.*.exists' => 'Uno o más de los laboratorios seleccionados no existen.',

            'name.required' => 'El nombre del grupo es obligatorio.',
            'name.string' => 'El nombre del grupo debe ser una cadena de texto.',
            'name.max' => 'El nombre del grupo no puede exceder los 20 caracteres.',

            'start_time.required' => 'La hora de inicio es obligatoria.',
            'start_time.date_format' => 'La hora de inicio debe tener el formato Y-m-d H:i.',

            'end_time.required' => 'La hora de finalización es obligatoria.',
            'end_time.date_format' => 'La hora de finalización debe tener el formato Y-m-d H:i.',
            'end_time.after' => 'La hora de finalización debe ser posterior a la hora de inicio.',
        ];
    }
}
