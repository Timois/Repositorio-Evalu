<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuestionEvaluation extends Model
{
    protected $table = 'question_evaluation';

    protected $fillable = [
        'evaluation_id',
        'bank_question_id'
    ];
    public function evaluations():BelongsTo {
        return $this->belongsTo(Evaluation::class);
    }

    public function bank_questions():BelongsTo {
        return $this->belongsTo(QuestionBank::class);
    }
}   

