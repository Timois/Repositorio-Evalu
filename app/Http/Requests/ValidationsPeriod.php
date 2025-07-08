<?php

namespace App\Http\Requests;

use App\Models\Period;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
class ValidationsPeriod extends FormRequest
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
    $id = $this->route("id");

    return [
        'period' => [
            'required_if:id,null',
            'regex:/^[A-Za-zñÑáéíóúÁÉÍÓÚ0-9\s]*$/',
            'max:20',
            function ($attribute, $value, $fail) use ($id) {
                $normalized = Str::lower(preg_replace('/\s+/', '', $value)); // convierte a minúsculas y quita espacios

                $exists = \App\Models\Period::all()
                    ->filter(function ($period) use ($normalized, $id) {
                        $existingNormalized = Str::lower(preg_replace('/\s+/', '', $period->period));
                        return $existingNormalized === $normalized && $period->id != $id;
                    })->isNotEmpty();

                if ($exists) {
                    $fail('El periodo ya existe (sin importar mayúsculas o espacios).');
                }
            }
        ],
        'level' => ['required_if:id,null', 'in:1,2,3,4,5'],
    ];
}

    public function messages()
    {
        return [
            'period.required' => 'El periodo es obligatorio.',
            'period.regex' => 'El periodo tiene que ser numeros y letras',
            'period.unique' => 'El nombre del periodo ya esta en uso. Por favor, elige otro.',
            'level.required' => 'El nivel del periodo es obligatorio',
            'level.in' => 'el nivel debe estar en el rango del numero 1 al 5',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(
            function ($validator) {
                $period = $this->period;
                $level = $this->level;
                $exists = Period::where('period', '=', $period, 'and')
                    ->where('level', '=', $level)->exists();
                if ($exists) {
                    $validator->errors()->add('period', 'Ya existe ese periodo y nivel',);
                }
                // Validar que si el periodo se llama "semestre", el nivel solo puede ser 1 o 2
                if ($period === 'semestre' && !in_array($level, [1, 2])) {
                    $validator->errors()->add('level', 'El semestre solo puede registrase en los niveles 1 o 2.');
                }
                if ($period === 'anual' && !in_array($level, [1])) {
                    $validator->errors()->add('level', 'El año solo puede registrase en los niveles 1.');
                }
                if ($period === 'mesa' && !in_array($level, [4, 5])) {
                    $validator->errors()->add('level', 'La mesa solo puede registrase en los niveles 4 o 5.');
                }
                if ($period === 'verano' && !in_array($level, [3])) {
                    $validator->errors()->add('level', 'El verano solo puede registrase en el nivel 3.');
                }
            }
        );
    }
}
