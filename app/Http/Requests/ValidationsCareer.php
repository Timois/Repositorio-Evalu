<?php

namespace App\Http\Requests;

use App\Models\Career;
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
        $validationName = 'required|unique:careers,name|regex:/[a-zA-Zñ]+/';
        $validationInitials = 'required|unique:careers,initials|max:10|regex:/^[\pL\s\-]+$/u';
        $validationLogo = 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048';
        $validationType = 'required|in:dependiente,mayor,carrera,facultad';
        
        // Validación condicional para unit_id basada en el tipo
        $validationUnitId = function($attribute, $value, $fail) {
            $type = $this->input('type');
            
            // Para tipos independientes
            if (in_array($type, Career::INDEPENDENT_TYPES) && $value != 0) {
                $fail('Las unidades de tipo mayor o facultad no deben tener unit_id.');
            }
            
            // Para tipos dependientes (carrera y dependiente)
            if (in_array($type, Career::DEPENDENT_TYPES)) {
                if ($value == 0) {
                    $fail('Las carreras y dependientes deben tener un unit_id válido.');
                }
                
                $parentExists = Career::where('id', $value)
                    ->whereIn('type', Career::INDEPENDENT_TYPES)
                    ->exists();
                    
                if (!$parentExists) {
                    $fail('Las carreras y dependientes deben pertenecer a una facultad o mayor válido.');
                }
            }
        };

        // Si es una actualización
        $career = $this->route('id');
        if ($career) {
            $validationName = 'string|max:50|regex:/[a-zA-Zñ]+/|unique:careers,name,' . $career;
            $validationInitials = 'string|max:10|unique:careers,initials,' . $career;
            $validationLogo = 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048';
            $validationType = 'required|in:dependiente,mayor,carrera,facultad';
            $validationUnitId = $validationUnitId;
        }

        return [
            'name' => $validationName,
            'initials' => $validationInitials,
            'logo' => $validationLogo,
            'type' => $validationType,
            'unit_id' => ['required', $validationUnitId]
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
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'Solo debe contener letras.',
            'name.unique' => "El nombre de la carrera ya existe. ID de la carrera existente: " . (($career_name) ? $career_name->id:0),
            'initials.unique' => "La sigla de la carrera ya existe. ID de la carrera existente: " . (($career_initials) ? $career_initials->id:0),
            'initial.max' => 'Las siglas no deben pasar de 10 letras. ',
            'initials.regex' => 'Solo debe contener letras.',
            'logo.image' => 'El archivo subido debe ser una imagen válida.',
            'logo.mimes' => 'La imagen debe estar en uno de los siguientes formatos: jpeg, png, jpg, webp, svg.',
            'logo.max' => 'La imagen no debe superar los 2 MB.',
            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'El tipo debe ser "dependiente", "mayor", "carrera" o "facultad".',
            'unit_id.required' => 'El unit_id es obligatorio.',
            'unit_id.exists' => 'El unit_id debe ser válido.',
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
