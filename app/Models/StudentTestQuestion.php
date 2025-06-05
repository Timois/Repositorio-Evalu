<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentTestQuestion extends Model
{
    protected $table = 'student_test_questions';
    protected $fillable = [
        'student_test_id',
        'question_id',
        'score_assigned',
        'student_answer',
        'is_correct',
        'question_order',
    ];

    // Relación con el examen del estudiante
    public function studentTest()
    {
        return $this->belongsTo(StudentTest::class);
    }

    // Relación con la pregunta del banco
    public function question()
    {
        return $this->belongsTo(QuestionBank::class);
    }
}
