<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAnswer extends Model
{
    protected $table = 'student_answers';
    protected $fillable = [
        'student_test_id',
        'question_id',
        'answer_id',
        'score',
    ];

    public function studentTest()
    {
        return $this->belongsTo(StudentTest::class, 'student_test_id');
    }
    public function question()
    {
        return $this->belongsTo(QuestionBank::class, 'question_id');
    }
    public function answer()
    {
        return $this->belongsTo(AnswerBank::class, 'answer_id');
    }
}
