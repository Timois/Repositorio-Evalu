<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ValidationsUnit extends FormRequest
{
    /**6
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
        $validationName = 'required|string|max:255|regex:/^[\pL\s,\-.]+$/u|unique:units,name';
        $validationSigla = 'required|string|max:10|regex:/^[\pL\s\-]+$/u|unique:units,initials';
        $validationLogo = 'required|image|mimes:jpeg,png,jpg,webp,svg|max:2048';
        $validationType = 'required|in:facultad,unidad';
        $unit = $this->route("id");
        if ($unit) {
            $validationName = 'required|string|max:255|regex:/^[\pL\s,\-.]+$/u|unique:units,name';
            $validationSigla = 'string|max:10|regex:/^[\pL\s\-]+$/u|unique:units,initials,' . $unit;
            $validationLogo = 'image|mimes:jpeg,png,jpg,webp,svg|max:2048';
            $validationType = 'in:facultad,unidad';
        }   

        return [    
            'name' => $validationName,
            'initials' => $validationSigla,
            'logo' => $validationLogo,
            'type' => $validationType,
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {   
            $this->merge([
                'name' => strtolower($this->name),
                'initials' => strtoupper($this->initials),
            ]);
    }

    public function messages(): array
    {
        $sigla = $this->request->filter('initials');
        $unit_sigla = DB::table('units')->where('initials', '=', $sigla)->first();

        $name = $this->request->filter('name');
        $unit_name = DB::table('units')->where('name', '=', $name)->first();

        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'Solo debe contener letras.',
            'name.unique' => "El nombre de la unidad ya existe. ID de la unidad existente: " . (($unit_name) ? $unit_name->id:0),
            'initials.required' => 'La sigla es obligatorio.',
            'initials.unique' => "La sigla de la unidad ya existe. ID de la unidad existente: " . (($unit_sigla) ? $unit_sigla->id:0),
            'initials.regex' => 'Solo debe contener letras.',
            'initial.max' => 'Las siglas no deben pasar de 10 letras. ',
            'logo.required' => 'La imagen es obligatoria.',
            'logo.image' => 'El archivo debe ser una imagen.',
            'logo.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, webp, o svg.',
            'logo.max' => 'La imagen no debe ser mayor a 2MB.',
            'type.required' => 'El campo tipo es obligatorio.',
            'type.in' => 'El tipo debe ser facultad o unidad.',
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
