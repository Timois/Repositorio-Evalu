<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionBank extends Model
{
    protected $table = "bank_questions";

    protected $fillable = [
        'area_id',
        'excel_import_id',
        'question',
        'description',
        'type',
        'image',
        'total_weight',
        'status',
    ];

    public function bank_answers():HasMany{
        return $this->hasMany(AnswerBank::class, 'bank_question_id');
    }

    public function areas():BelongsTo{
        return $this->belongsTo(Areas::class);
    }
    
    public function excel_imports():HasMany{
        return $this->hasMany(ExcelImports::class);
    }

    public function questionEvaluation()
    {
        return $this->belongsTo(QuestionEvaluation::class, 'question_evaluation_id');
    }
}
