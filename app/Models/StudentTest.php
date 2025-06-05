<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class StudentTest extends Pivot
{
    protected $table = 'student_tests';

    protected $fillable = [
        'student_id',
        'evaluation_id',
        'code',
        'start_time',
        'end_time',
        'correct_answers',
        'incorrect_answers',
        'not_answered',
        'score_obtained',
        'status',
        'questions_order',
    ];

    protected $casts = [
        'questions_order' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }
    public function studentTestQuestions()
    {
        return $this->hasMany(StudentTestQuestion::class);
    }
    public function result()
    {
        return $this->hasOne(Result::class);
    }
}
