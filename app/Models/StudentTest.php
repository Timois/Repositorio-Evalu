<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentTest extends Model
{
    protected $table = 'student_tests';
    
    protected $casts = [
        'questions_order' => 'array', // convierte automáticamente el JSON a array
    ];
    public function student():BelongsTo{
        return $this->belongsTo(Student::class);
    }

    public function evaluations():BelongsTo{
        return $this->belongsTo(Evaluation::class);
    }
    public function results():HasMany{
        return $this->hasMany(Result::class);
    }
    public function student_answer():HasMany{
        return $this->hasMany(StudentAnswer::class);
    }
}
