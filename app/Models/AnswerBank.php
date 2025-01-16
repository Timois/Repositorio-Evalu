<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

   public function bank_questions():HasMany{
        return $this->hasMany(QuestionBank::class);
   }
}
