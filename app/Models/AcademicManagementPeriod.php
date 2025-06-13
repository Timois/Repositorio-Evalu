<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicManagementPeriod extends Model
{
    protected $table = "academic_management_period";

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }
    public function academicManagementCareer(): BelongsTo
    {
        return $this->belongsTo(AcademicManagementCareer::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function questions()
    {
        return $this->belongsToMany(
            QuestionBank::class,
            'academic_management_period_bank_question',
            'bank_question_id',
            'academic_management_period_id'
        )->withTimestamps();
    }
}
