<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    protected $table = 'evaluations';
    protected $fillable = [
        'title',
        'description',
        'total_score',
        'passing_score',
        'date_of_realization',
        'code',
        'qualified_students',
        'status',
        'type',
        'academic_management_period_id'
    ];
    public function academicManagementPeriod()
    {
        return $this->belongsTo(AcademicManagementPeriod::class, 'academic_management_period_id');
    }
    public function students_test(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_tests', 'evaluation_id', 'student_id')
            ->using(StudentTest::class)
            ->withPivot([
                'code',
                'start_time',
                'end_time',
                'correct_answers',
                'incorrect_answers',
                'not_answered',
                'score_obtained',
                'status',
                'questions_order',
            ])
            ->withTimestamps();
    }
    public function areaScores()
    {
        return $this->hasMany(EvaluationAreaScore::class);
    }

    public function questionEvaluations(): HasMany
    {
        return $this->hasMany(QuestionEvaluation::class);
    }

    public function backupGenerateQuestions(): HasMany
    {
        return $this->hasMany(BackupGenerateQuestion::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}
