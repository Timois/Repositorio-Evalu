<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentTest extends Model
{
    protected $table = 'student_test';
    

    public function student():BelongsTo{
        return $this->belongsTo(Student::class);
    }

    public function draft_exams():HasMany{
        return $this->hasMany(DraftExamResult::class);
    }

    public function evaluations():BelongsTo{
        return $this->belongsTo(Evaluation::class);
    }
}
