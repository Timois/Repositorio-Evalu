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
            'areas.*.cantidadFacil' => 'nullable|integer|min:0',
            'areas.*.cantidadMedia' => 'nullable|integer|min:0',
            'areas.*.cantidadDificil' => 'nullable|integer|min:0',
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
                    if (!isset($area['cantidadFacil'])) {
                        $validator->errors()->add("areas.$index.cantidadFacil", 'Este campo es obligatorio cuando ponderar es true.');
                    }
                    if (!isset($area['cantidadMedia'])) {
                        $validator->errors()->add("areas.$index.cantidadMedia", 'Este campo es obligatorio cuando ponderar es true.');
                    }
                    if (!isset($area['cantidadDificil'])) {
                        $validator->errors()->add("areas.$index.cantidadDificil", 'Este campo es obligatorio cuando ponderar es true.');
                    }
                } else {
                    if (!isset($area['cantidadTotal'])) {
                        $validator->errors()->add("areas.$index.cantidadTotal", 'Este campo es obligatorio cuando ponderar es false.');
                    }
                }
            }
        });
    }
}
