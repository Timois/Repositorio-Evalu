<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class AnswerBank extends Model
{
   protected $table = "bank_answers";
   
   protected $fillable = [
      'bank_question_id',
      'answer',
      'image',
      'weight',
      'is_correct',
      'status',
  ];

   public function bank_questions():BelongsTo{
        return $this->belongsTo(QuestionBank::class);
   }
}
