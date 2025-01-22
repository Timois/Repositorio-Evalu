<?php

namespace App\Http\Requests;

use App\Models\QuestionEvaluation;
use Illuminate\Foundation\Http\FormRequest;

class ValidationAssignQuestion extends FormRequest
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
            'bank_question_id'=>['required','exists:bank_questions,id'],
            'evaluation_id'=>['required','exists:evaluations,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $bank_question_id = $this->bank_question_id;
            $evaluation_id = $this->evaluation_id;
            $assign = QuestionEvaluation::where('bank_question_id','=',$bank_question_id,'and')
            ->where('evaluation_id','=',$evaluation_id)->first();

            if($assign){
                $validator->errors()->add('bank_question_id','la pregunta ya ha sido asignada a esta evaluacion');
            }
        });
    }  
    
    public function messages(){
        return[
            'bank_question_id.required'=>'la pregunta es obligatoria',
            'bank_question_id.exists'=>'la pregunta no existe',
            'evaluation_id.required'=>'la evaluacion es obligatoria',
            'evaluation_id.exists'=>'la evaluacion no existe',
        ];
    }
}
