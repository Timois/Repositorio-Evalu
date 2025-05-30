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

    public function evaluations(): HasMany
    {
        return $this->hasMany(StudentTest::class);
    }

    public function periods(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademicManagementPeriod::class,
            'academic_management_period_student',
            'student_id',                    
            'academic_management_period_id' 
        )->withTimestamps();
    }
}
