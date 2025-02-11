<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidationGaussianCurvature extends FormRequest
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
        $validationRulesTestId = 'required|exists:rules_tests,id';
        $validationPassingScore = 'required|numeric|min:0';
        $validationMaximunScore = 'required|numeric|min:0';
        $validationApprovalCount = 'required|numeric|min:0';
        $validationFailedCount = 'required|numeric|min:0';
        $validationAbandonedCount = 'required|numeric|min:0';
        $validationAprobalPercentage = 'required|numeric|min:0|max:100|regex:/^\d+(\.\d{1,2})?$/';
        $validationFailedPercentage = 'required|numeric|min:0|max:100|regex:/^\d+(\.\d{1,2})?$/';
        $validationAbandonedPercentage = 'required|numeric|min:0|max:100|regex:/^\d+(\.\d{1,2})?$/';
        $validationTotalPercentage = 'required|numeric|min:0|max:100|regex:/^\d+(\.\d{1,2})?$/';
        $validationStatus = 'required|in:activo,inactivo';
        if ($this->route('id')) {
            $validationRulesTestId = 'required|exists:rules_tests,id';
            $validationPassingScore = 'required|numeric|min:0';
            $validationMaximunScore = 'required|numeric|min:0';
            $validationApprovalCount = 'required|numeric|min:0';
            $validationFailedCount = 'required|numeric|min:0';
            $validationAbandonedCount = 'required|numeric|min:0';
            $validationAprobalPercentage = 'required|numeric|min:0|max:100|regex:/^\d+(\.\d{1,2})?$/';
            $validationFailedPercentage = 'required|numeric|min:0|max:100|regex:/^\d+(\.\d{1,2})?$/';
            $validationAbandonedPercentage = 'required|numeric|min:0|max:100|regex:/^\d+(\.\d{1,2})?$/';
            $validationTotalPercentage = 'required|numeric|min:0|max:100|regex:/^\d+(\.\d{1,2})?$/';
            $validationStatus = 'required|in:activo,inactivo,en_proceso';
        }

        return [
            'rules_test_id' => $validationRulesTestId,
            'passing_score' => $validationPassingScore,
            'maximun_score' => $validationMaximunScore, 
            'approval_count' => $validationApprovalCount,
            'failed_count' => $validationFailedCount,
            'abandoned_count' => $validationAbandonedCount,
            'aprobal_percentage' => $validationAprobalPercentage,
            'failed_percentage' => $validationFailedPercentage,
            'abandoned_percentage' => $validationAbandonedPercentage,
            'total_percentage' => $validationTotalPercentage,
            'status' => $validationStatus
        ];
    }

    public function messages()
    {
        return [
            'rules_test_id.required' => 'El campo id de prueba es obligatorio.',
            'rules_test_id.exists' => 'El campo Id de prueba no existe.',
            'passing_score.required' => 'El campo nota de aprobacion es obligatorio.',
            'passing_score.numeric' => 'El campo nota de aprobacion debe ser un número.',
            'passing_score.min' => 'El campo nota de aprobacion debe ser mayor o igual a 0.',
            'maximun_score.required' => 'El campo nota maxima es obligatorio.',
            'maximun_score.numeric' => 'El campo nota maxima debe ser un número.',
            'maximun_score.min' => 'El campo nota maxima debe ser mayor o igual a 0.',
            'approval_count.required' => 'El campo cantidad de aprobados es obligatorio.',
            'approval_count.numeric' => 'El campo cantidad de aprobados debe ser un número.',
            'approval_count.min' => 'El campo cantida de aprobados debe ser mayor o igual a 0.',
            'failed_count.required' => 'El campo cantidad de reprobados es obligatorio.',
            'failed_count.numeric' => 'El campo cantidad de reprobados debe ser un número.',
            'failed_count.min' => 'El campo cantidad de reprobados debe ser mayor o igual a 0.',
            'abandoned_count.required' => 'El campo cantidad de abandonados es obligatorio.',
            'abandoned_count.numeric' => 'El campo cantidad de abandonados debe ser un número.',
            'abandoned_count.min' => 'El campo cantidad de abandonados debe ser mayor o igual a 0.',
            'aprobal_percentage.required' => 'El campo porcentaje de aprobados es obligatorio.',
            'aprobal_percentage.numeric' => 'El campo porcentaje de aprobados debe ser un número.',
            'aprobal_percentage.min' => 'El campo porcentaje de aprobados debe ser mayor o igual a 0.',
            'aprobal_percentage.max' => 'El campo porcentaje de aprobados debe ser menor o igual a 100.',
            'failed_percentage.required' => 'El campo porcentaje de reprobados es obligatorio.',
            'failed_percentage.numeric' => 'El campo porcentaje de reprobados debe ser un número.',
            'failed_percentage.min' => 'El campo porcentaje de reprobados debe ser mayor o igual a 0.',
            'failed_percentage.max' => 'El campo porcentaje de reprobados debe ser menor o igual a 100.',
            'abandoned_percentage.required' => 'El campo porcentaje de abandonos es obligatorio.',
            'abandoned_percentage.numeric' => 'El campo porcentaje de abandonos debe ser un número.',
            'abandoned_percentage.min' => 'El campo porcentaje de abandonos debe ser mayor o igual a 0.',
            'abandoned_percentage.max' => 'El campo porcentaje de abandonos debe ser menor o igual a 100.',
            'total_percentage.required' => 'El campo porcentaje total es obligatorio.',
            'total_percentage.numeric' => 'El campo porcentaje total debe ser un número.',
            'total_percentage.min' => 'El campo porcentaje total debe ser mayor o igual a 0.',
            'total_percentage.max' => 'El campo porcentaje total debe ser menor o igual a 100.',
            'status.required' => 'El campo status es obligatorio.',
            'status.in' => 'El campo status debe ser "activo", "inactivo" o "en_proceso".',
        ];
    }
}
