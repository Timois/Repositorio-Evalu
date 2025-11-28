<?php

namespace App\Http\Requests;

use App\Models\Evaluation;
use App\Models\Laboratorie;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class ValidationGroup extends FormRequest
{
    public function rules(): array
    {
        return [
            'evaluation_id' => ['required', 'exists:evaluations,id'],

            'laboratory_ids' => ['required', 'array', 'min:1'],
            'laboratory_ids.*' => ['integer', 'exists:laboratories,id'],

            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->evaluation_id && $this->filled('start_time') && $this->filled('end_time')) {
                $evaluation = Evaluation::find($this->evaluation_id);
                // Convertir datetime real enviado desde frontend
                $start = Carbon::parse($this->start_time);
                $end   = Carbon::parse($this->end_time);
                $durationMinutes = $start->diffInMinutes($end);
                if ($durationMinutes > $evaluation->time) {
                    $validator->errors()->add(
                        'end_time',
                        "La duración del grupo ({$durationMinutes} min) excede la duración del examen ({$evaluation->time} min)."
                    );
                }
            }
            // Validar capacidad de laboratorios
            if ($this->has('laboratory_ids')) {
                $invalidLabs = Laboratorie::whereIn('id', $this->laboratory_ids)
                    ->where('equipment_count', '<=', 3)
                    ->pluck('name');
                if ($invalidLabs->count() > 0) {
                    $validator->errors()->add(
                        'laboratory_ids',
                        "Los siguientes laboratorios no tienen capacidad suficiente (mínimo 4 equipos): " .
                            $invalidLabs->implode(', ')
                    );
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'name' => $this->has('name') ? trim($this->name) : null,
            'description' => $this->has('description') ? trim($this->description) : null,
        ]);

        if ($this->has('laboratory_ids') && !is_array($this->laboratory_ids)) {
            $ids = $this->laboratory_ids;
            if (is_string($ids)) {
                $ids = json_decode($ids, true) ?: explode(',', $ids);
            }
            $this->merge(['laboratory_ids' => array_map('intval', $ids)]);
        }
    }

    public function messages(): array
    {
        return [
            'evaluation_id.required' => 'Debe seleccionar una evaluación.',
            'evaluation_id.exists'   => 'La evaluación seleccionada no existe.',
            'laboratory_ids.required' => 'Debe seleccionar al menos un laboratorio.',
            'laboratory_ids.array'    => 'Los laboratorios deben enviarse como un arreglo.',
            'laboratory_ids.min'      => 'Debe seleccionar al menos un laboratorio.',
            'laboratory_ids.*.exists' => 'Uno o más laboratorios seleccionados no existen.',
            'laboratory_ids.*.distinct' => 'No se pueden seleccionar laboratorios repetidos.',
            'name.required' => 'El nombre base de los grupos es obligatorio.',
            'name.max'      => 'El nombre no puede exceder los 100 caracteres.',
            'description.max' => 'La descripción no debe exceder los 500 caracteres.',
            'start_time.required' => 'La fecha y hora de inicio es obligatoria.',
            'start_time.date'     => 'La fecha y hora de inicio no es válida.',
            'end_time.required' => 'La fecha y hora de finalización es obligatoria.',
            'end_time.date'     => 'La fecha y hora de finalización no es válida.',
            'end_time.after'    => 'La fecha/hora final debe ser posterior a la de inicio.',
            'order_type.required' => 'Debe seleccionar el tipo de ordenación de estudiantes.',
            'order_type.in'       => 'El tipo de orden debe ser "alphabetical" o "id_asc".',
        ];
    }
}
