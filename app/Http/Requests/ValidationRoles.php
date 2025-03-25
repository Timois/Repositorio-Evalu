<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidationRoles extends FormRequest
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
        $validationName = 'required|string|max:50|regex:/^[a-zA-Z\s-]+$/';
        $validationPermisions = 'required|array';
        // Si estamos actualizando un rol, excluir su propio ID de la validación de unicidad
        if ($this->route("id")) {
            $validationName = 'required|string|max:50|regex:/^[a-zA-Z\s-]+$/|unique:roles,name,' . $this->route("id");
            $validationPermisions = 'required|array';
        }
        return [
            'name' => $validationName,
            'permissions' => $validationPermisions
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'Solo debe contener letras.',
            'name.unique' => 'El nombre de rol ya existe.',
            'permissions.required' => 'Debe seleccionar al menos un permiso.',
        ];
    }
}
