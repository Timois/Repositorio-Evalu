<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentTest extends Model
{
    protected $table = 'student_tests';
    protected $fillable = [
        'evaluation_id',
        'student_id',
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
        'questions_order' => 'array', // convierte automáticamente el JSON a array
    ];
    public function student():BelongsTo{
        return $this->belongsTo(Student::class);
    }

    public function evaluation():BelongsTo{
        return $this->belongsTo(Evaluation::class);
    }
    public function results():HasMany{
        return $this->hasMany(Result::class);
    }
    public function student_answer():HasMany{
        return $this->hasMany(StudentAnswer::class);
    }

    public function backup_answer():HasMany{
        return $this->hasMany(BackupAnswerTest::class);
    }
}
