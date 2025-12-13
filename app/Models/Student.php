<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $table = 'students';

    protected $fillable = [
        'ci',
        'academic_management_period_id',
        'name',
        'paternal_surname',
        'maternal_surname',
        'phone_number',
        'birthdate',
        'password'
    ];

    public function evaluations(): BelongsToMany
    {
        return $this->belongsToMany(Evaluation::class, 'student_tests', 'student_id', 'evaluation_id')
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
    public function groups()
    {
        return $this->belongsToMany(Group::class)->withTimestamps();
    }
    public function studentTests()
    {
        return $this->hasMany(StudentTest::class, 'student_id');
    }
}
