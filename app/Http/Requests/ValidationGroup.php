<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Group;
use Carbon\Carbon;

class ValidationGroup extends FormRequest
{
    public function rules(): array
    {
        $groupId = $this->route('id');

        $validationEvaluationId = $groupId ? 'sometimes|exists:evaluations,id' : 'required|exists:evaluations,id';
        $validationLaboratoryId = $groupId ? 'sometimes|exists:laboratories,id' : 'required|exists:laboratories,id';
        $validationName = $groupId ? 'sometimes|string|max:20' : 'required|string|max:20';
        $validationStartTime = $groupId ? 'sometimes|date_format:H:i' : 'required|date_format:H:i';
        $validationEndTime = $groupId ? 'sometimes|date_format:H:i|after:start_time' : 'required|date_format:H:i|after:start_time';
        $validationOrderType = $groupId ? 'sometimes|in:alphabetical,id_asc' : 'required|in:alphabetical,id_asc';
        $validationDescription = $groupId? 'sometimes|string|max:255' : 'required|string|max:255';
        return [
            'evaluation_id' => $validationEvaluationId,
            'laboratory_id' => $validationLaboratoryId,
            'name' => $validationName,
            'description' => $validationDescription,
            'start_time' => $validationStartTime,
            'end_time' => $validationEndTime,
            'order_type' => $validationOrderType
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

            // Validar que ambos sean el mismo día que date_of_realization
            if (!$startTime->isSameDay($examDate)) {
                $validator->errors()->add('start_time', 'La hora de inicio debe corresponder a la fecha del examen: ' . $examDate->format('H:i'));
            }

            if (!$endTime->isSameDay($examDate)) {
                $validator->errors()->add('end_time', 'La hora de fin debe corresponder a la fecha del examen: ' . $examDate->format('H:i'));
            }

            // Validar que la duración no supere el tiempo del examen
            $durationMinutes = $startTime->diffInMinutes($endTime);
            if ($durationMinutes > $evaluation->time) {
                $validator->errors()->add('end_time', 'La duración del grupo no puede exceder la duración del examen (' . $evaluation->time . ' minutos).');
            }

            // Validar que no haya solapamiento de horarios en el mismo laboratorio
            $groupId = $this->route('id');
            $laboratoryId = $this->laboratory_id;

            $overlappingGroup = Group::where('laboratory_id', $laboratoryId)
                ->where('evaluation_id', $this->evaluation_id) // Mismo examen
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where(function ($q) use ($startTime, $endTime) {
                        // El nuevo grupo comienza dentro de un grupo existente
                        $q->where('start_time', '<=', $startTime)
                          ->where('end_time', '>=', $startTime);
                    })->orWhere(function ($q) use ($startTime, $endTime) {
                        // El nuevo grupo termina dentro de un grupo existente
                        $q->where('start_time', '<=', $endTime)
                          ->where('end_time', '>=', $endTime);
                    })->orWhere(function ($q) use ($startTime, $endTime) {
                        // El nuevo grupo abarca completamente un grupo existente
                        $q->where('start_time', '>=', $startTime)
                          ->where('end_time', '<=', $endTime);
                    })->orWhere(function ($q) use ($startTime, $endTime) {
                        // Un grupo existente abarca completamente el nuevo grupo
                        $q->where('start_time', '<=', $startTime)
                          ->where('end_time', '>=', $endTime);
                    });
                });

            if ($groupId) {
                $overlappingGroup->where('id', '!=', $groupId); // Excluir el grupo actual en caso de actualización
            }

            if ($overlappingGroup->exists()) {
                $validator->errors()->add('start_time', 'El horario seleccionado se solapa con otro grupo en el mismo laboratorio.');
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
            'laboratory_id.required' => 'Debe seleccionar al menos un laboratorio.',
            'laboratory_id.exists' => 'El laboratorio seleccionado no existe.',
            'name.required' => 'El nombre del grupo es obligatorio.',
            'name.string' => 'El nombre del grupo debe ser una cadena de texto.',
            'name.max' => 'El nombre del grupo no puede exceder los 20 caracteres.',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'start_time.date_format' => 'La hora de inicio debe tener el formato H:i.',
            'end_time.required' => 'La hora de finalización es obligatoria.',
            'end_time.date_format' => 'La hora de finalización debe tener el formato H:i.',
            'end_time.after' => 'La hora de finalización debe ser posterior a la hora de inicio.',
            'order_type.required' => 'El tipo de orden es obligatorio.',
            'order_type.in' => 'El tipo de orden debe ser "alphabetical" o "id_asc".',
        ];
    }
}