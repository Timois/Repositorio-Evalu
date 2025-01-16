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

    public function bank_answers():BelongsTo{
        return $this->belongsTo(AnswerBank::class)
        ->withTimestamps();
    }

    public function areas():HasMany{
        return $this->hasMany(Areas::class);
    }
    
    public function excel_imports():HasMany{
        return $this->hasMany(ExcelImports::class);
    }

    public function evaluations_area():HasMany{
        return $this->hasMany(EvaluationsArea::class);
    }
}
