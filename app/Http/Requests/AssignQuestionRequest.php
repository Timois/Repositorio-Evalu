<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignQuestionRequest extends FormRequest
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
            'ponderar' => 'required|boolean',
            'areas' => 'required|array|min:1',
            'areas.*.id' => 'required|exists:areas,id',
            'areas.*.nota' => 'required|numeric|min:0.01',

            // Si ponderar = true
            'areas.*.cantidadFacil' => 'nullable|integer|min:0',
            'areas.*.cantidadMedia' => 'nullable|integer|min:0',
            'areas.*.cantidadDificil' => 'nullable|integer|min:0',

            // Si ponderar = false
            'areas.*.cantidadTotal' => 'nullable|integer|min:1',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'ponderar' => filter_var($this->ponderar, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $ponderar = $this->boolean('ponderar');

            foreach ($this->areas as $index => $area) {
                if ($ponderar) {
                    $sum = ($area['cantidadFacil'] ?? 0) + ($area['cantidadMedia'] ?? 0) + ($area['cantidadDificil'] ?? 0);
                    if ($sum === 0) {
                        $validator->errors()->add("areas.$index", 'Debe asignar al menos una pregunta (fácil, media o difícil).');
                    }
                } else {
                    if (($area['cantidadTotal'] ?? 0) < 1) {
                        $validator->errors()->add("areas.$index.cantidadTotal", 'Debe asignar al menos una pregunta en cantidadTotal.');
                    }
                }
            }
        });
    }
}
