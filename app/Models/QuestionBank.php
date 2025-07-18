<?php

namespace App\Models;

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
        'dificulty',
        'type',
        'image',
        'total_weight',
        'status',
    ];

    public function bank_answers(): HasMany
    {
        return $this->hasMany(AnswerBank::class, 'bank_question_id');
    }

    public function areas(): BelongsTo
    {
        return $this->belongsTo(Areas::class);
    }

    public function excel_imports(): HasMany
    {
        return $this->hasMany(ExcelImports::class);
    }

    public function questionEvaluation()
    {
        return $this->belongsTo(QuestionEvaluation::class, 'question_evaluation_id');
    }
    public function studentAnswer()
    {
        return $this->hasMany(StudentAnswer::class);
    }
    public function academicManagementPeriod()
    {
        return $this->belongsToMany(
            AcademicManagementPeriod::class,
            'academic_management_period_bank_question', // nombre real de la tabla
            'bank_question_id',                        // clave foránea local
            'academic_management_period_id'            // clave foránea del modelo relacionado
        )->withTimestamps();
    }
    public function studentTestQuestions()
    {
        return $this->hasMany(StudentTestQuestion::class, 'question_id');
    }
}
