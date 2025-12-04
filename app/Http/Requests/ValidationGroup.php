<?php

namespace App\Http\Requests;

use App\Models\Evaluation;
use App\Models\Group;
use App\Models\Laboratorie;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class ValidationGroup extends FormRequest
{
    public function rules(): array
    {
        return [
            'evaluation_id' => ['required', 'exists:evaluations,id'],
            'laboratory_id' => ['required', 'integer', 'exists:laboratories,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                // Único por evaluación
                function ($attribute, $value, $fail) {
                    if ($this->evaluation_id && Group::where('evaluation_id', $this->evaluation_id)
                        ->where('name', $value)
                        ->exists()
                    ) {
                        $fail("Ya existe un grupo con el nombre '{$value}' para esta evaluación.");
                    }
                },
            ],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date'],
            'end_time'   => ['required', 'date', 'after:start_time'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validación duración del grupo
            if ($this->evaluation_id && $this->filled('start_time') && $this->filled('end_time')) {
                $evaluation = Evaluation::find($this->evaluation_id);
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
            // Validar capacidad del laboratorio único
            if ($this->has('laboratory_id')) {
                $lab = Laboratorie::find($this->laboratory_id);
                if ($lab && $lab->equipment_count <= 3) {
                    $validator->errors()->add(
                        'laboratory_id',
                        "El laboratorio '{$lab->name}' no tiene capacidad suficiente (mínimo 4 equipos)."
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
        // Convertir laboratory_id a entero
        if ($this->has('laboratory_id')) {
            $this->merge(['laboratory_id' => (int) $this->laboratory_id]);
        }
    }

    public function messages(): array
    {
        return [
            'evaluation_id.required' => 'Debe seleccionar una evaluación.',
            'evaluation_id.exists'   => 'La evaluación seleccionada no existe.',
            'laboratory_id.required' => 'Debe seleccionar un laboratorio.',
            'laboratory_id.integer'  => 'El laboratorio seleccionado no es válido.',
            'laboratory_id.exists'   => 'El laboratorio seleccionado no existe.',
            'name.required' => 'El nombre del grupo es obligatorio.',
            'name.max'      => 'El nombre no puede exceder los 255 caracteres.',
            'description.max' => 'La descripción no debe exceder los 500 caracteres.',
            'start_time.required' => 'La fecha y hora de inicio es obligatoria.',
            'start_time.date'     => 'La fecha y hora de inicio no es válida.',
            'end_time.required' => 'La fecha y hora de finalización es obligatoria.',
            'end_time.date'     => 'La fecha y hora de finalización no es válida.',
            'end_time.after'    => 'La fecha/hora final debe ser posterior a la de inicio.',
        ];
    }
}
