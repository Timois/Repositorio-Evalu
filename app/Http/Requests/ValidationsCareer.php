<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class ValidationsCareer extends FormRequest
{
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
        // Validación para creación o actualización
        $validationName = 'required|unique:careers,name|string|max:255|regex:/^[\pL\s\-]+$/u';
        $validationinitials = 'required|unique:careers,initials|string|max:255|regex:/^[\pL\s\-]+$/u';
        // Obtener el ID si es una actualización
        $career = $this->route("id");
        // Si es una actualización (hay un ID), se ignora el registro actual para la validación de unicidad
        if ($career) {
            $validationName = 'required|string|max:255|unique:careers,name,' . $career;
            $validationinitials = 'required|string|max:255|unique:careers,initials,' . $career;
        }

        return [
            'name' => $validationName,
            'initials' => $validationinitials,
            'logo' => 'required|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => strtolower($this->name)
            ]);
        }
        if ($this->has('initials')) {
            $this->merge([
                'initials' => strtoupper($this->initials),
            ]);
        }
    }

    public function messages(): array
    {

        $initials = $this->request->filter('initials');
        $career_initials = DB::table('careers')->where('initials', '=', $initials)->first();

        $name = $this->request->filter('name');
        $career_name = DB::table('careers')->where('name', '=', $name)->first();

        return [
            'name.unique' => "El nombre de la carrera ya existe. ID de la carrera existente: " . (($career_name) ? $career_name->id:0),
            'initials.unique' => "La sigla de la carrera ya existe. ID de la carrera existente: " . (($career_initials) ? $career_initials->id:0),
            'logo.required' => 'Debe subir una imagen para el campo path.',
            'logo.image' => 'El archivo subido debe ser una imagen válida.',
            'logo.mimes' => 'La imagen debe estar en uno de los siguientes formatos: jpeg, png, jpg, webp, svg.',
            'logo.max' => 'La imagen no debe superar los 2 MB.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 422)); // 422 Unprocessable Entity
    }
}
