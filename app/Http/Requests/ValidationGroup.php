<?php

namespace App\Http\Requests;

use App\Models\Evaluation;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class ValidationGroup extends FormRequest
{
    public function rules(): array
    {
        $groupId = $this->route('id');

        return [
            'evaluation_id' => ['required', 'exists:evaluations,id'],
            'laboratory_id' => $groupId
                ? 'sometimes|exists:laboratories,id'
                : 'required|exists:laboratories,id',
            'name' => [
                $groupId ? 'sometimes' : 'required',
                'string',
                'max:20',
                Rule::unique('groups')->where(function ($query) {
                    return $query->where('evaluation_id', $this->evaluation_id);
                })->ignore($groupId),
            ],
            'description' => $groupId ? 'sometimes|string|max:255' : 'nullable|string|max:255',
            'start_time' => $groupId ? 'sometimes|date_format:H:i' : 'nullable|date_format:H:i',
            'end_time' => [
                $groupId ? 'sometimes' : 'required',
                'date_format:H:i',
                'after:start_time',
            ],
            'order_type' => $groupId
                ? 'sometimes|in:alphabetical,id_asc'
                : 'required|in:alphabetical,id_asc',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $groupId = $this->route('id');
            $evaluationId = $this->evaluation_id;

            // Si estamos editando y no se proporcionó evaluation_id, obtenerlo del grupo
            if ($groupId && !$evaluationId) {
                $group = Group::find($groupId);
                if ($group) {
                    $evaluationId = $group->evaluation_id;
                }
            }

            $evaluation = Evaluation::find($evaluationId);
            if (!$evaluation) {
                $validator->errors()->add('evaluation_id', 'La evaluación no existe o no es válida.');
                return;
            }

            // Validación extra de tiempos respecto a la fecha de realización del examen
            if ($this->filled('start_time') && $this->filled('end_time')) {
                $examDate = Carbon::parse($evaluation->date_of_realization);

                $startTime = Carbon::parse($evaluation->date_of_realization . ' ' . $this->start_time);
                $endTime = Carbon::parse($evaluation->date_of_realization . ' ' . $this->end_time);

                if (!$startTime->isSameDay($examDate)) {
                    $validator->errors()->add('start_time', 'La hora de inicio debe corresponder a la fecha del examen.');
                }

                if (!$endTime->isSameDay($examDate)) {
                    $validator->errors()->add('end_time', 'La hora de fin debe corresponder a la fecha del examen.');
                }

                $durationMinutes = $startTime->diffInMinutes($endTime);
                if ($durationMinutes > $evaluation->time) {
                    $validator->errors()->add('end_time', 'La duración del grupo no puede exceder la duración del examen (' . $evaluation->time . ' minutos).');
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => strtolower(trim($this->input('name'))),
            ]);
        }
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
            'name.unique' => 'Ya existe un grupo con ese nombre en esta evaluación.',
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
