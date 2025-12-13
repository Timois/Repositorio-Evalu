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
            'evaluation_id' => ['required', 'integer', 'exists:evaluations,id'],
            // Laboratorios ahora son múltiples
            'laboratories'   => ['required', 'array', 'min:1'],
            'laboratories.*' => ['integer', 'exists:laboratories,id'],
            'start_time' => ['required', 'date_format:Y-m-d H:i:s'],
        ];
    }   

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            // Validar capacidad mínima por laboratorio
            if ($this->has('laboratories') && is_array($this->laboratories)) {

                $labs = Laboratorie::whereIn('id', $this->laboratories)->get();

                foreach ($labs as $lab) {
                    if ($lab->equipment_count <= 5) {
                        $validator->errors()->add(
                            'laboratories',
                            "El laboratorio '{$lab->name}' no tiene capacidad suficiente (mínimo 5 equipos)."
                        );
                    }
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        // Convertimos laboratories a enteros
        if ($this->has('laboratories')) {
            $this->merge([
                'laboratories' => array_map('intval', $this->laboratories)
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'evaluation_id.required' => 'Debe seleccionar una evaluación.',
            'laboratories.required'  => 'Debe seleccionar al menos un laboratorio.',
            'laboratories.array'     => 'Los laboratorios deben enviarse en formato de lista.',
            'laboratories.*.exists'  => 'Uno o más laboratorios seleccionados no existen.',
            'start_time.required'    => 'La fecha y hora de inicio es obligatoria.',
            'start_time.date_format' => 'La fecha y hora de inicio no tiene el formato correcto (Y-m-d H:i:s).',
        ];
    }
}
