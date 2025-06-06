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
        return [
            'evaluation_id' => 'required|exists:evaluations,id',
            'name' => 'required|string|max:20',
            'description' => 'nullable|string|max:255',
            'start_time' => 'required|date_format:Y-m-d H:i',
            'end_time' => 'required|date_format:Y-m-d H:i|after:start_time',
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

            // Parsear start_time y end_time
            $startTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $this->start_time);
            $endTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $this->end_time);

            // Validar que ambos sean el mismo día que date_of_realization
            $examDate = \Carbon\Carbon::parse($evaluation->date_of_realization);

            if (!$startTime->isSameDay($examDate)) {
                $validator->errors()->add('start_time', 'La hora de inicio debe ser en la misma fecha del examen: ' . $examDate->format('Y-m-d'));
            }

            if (!$endTime->isSameDay($examDate)) {
                $validator->errors()->add('end_time', 'La hora de fin debe ser en la misma fecha del examen: ' . $examDate->format('Y-m-d'));
            }

            // Validar que la duración no supere el tiempo del examen
            $durationMinutes = $startTime->diffInMinutes($endTime);

            if ($durationMinutes > $evaluation->time) {
                $validator->errors()->add('end_time', 'La duración del grupo no puede exceder la duración del examen (' . $evaluation->time . ' minutos).');
            }
        });
    }

    public function messages()
    {
        return [
            'evaluation_id.required' => 'El campo evaluación es obligatorio.',
            'evaluation_id.exists' => 'La evaluación seleccionada no existe.',
            'name.required' => 'El nombre del grupo es obligatorio.',
            'name.string' => 'El nombre del grupo debe ser una cadena de texto.',
            'name.max' => 'El nombre del grupo no puede exceder los 20 caracteres.',
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede exceder los 255 caracteres.',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'start_time.datetime_format' => 'La hora de inicio debe tener el formato Y-m-d H:i.',
            'end_time.required' => 'La hora de finalización es obligatoria.',
            'end_time.date_format' => 'La hora de finalización debe tener el formato Y-m-d H:i.',
            'end_time.after' => 'La hora de finalización debe ser posterior a la hora de inicio.',
        ];
    }
}
