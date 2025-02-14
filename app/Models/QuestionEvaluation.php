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
        'question_id',
        'score'
    ];

     // Asegurarse de que los campos no sean nullable
     protected $attributes = [
        'score' => 0,  // valor por defecto si es necesario
    ];
    public function evaluations():BelongsTo {
        return $this->belongsTo(Evaluation::class);
    }

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }
}   

