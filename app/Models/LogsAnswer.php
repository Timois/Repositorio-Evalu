<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogsAnswer extends Model
{
    protected $table = 'logs_answers';

     protected $fillable = [
        'student_test_id',
        'student_test_question_id',
        'answer_id',
        'time',
        'is_ultimate'
    ];

    public function studentTest()
    {
        return $this->belongsTo(StudentTest::class);
    }

    public function studentTestQuestion()
    {
        return $this->belongsTo(StudentTestQuestion::class);
    }
}
