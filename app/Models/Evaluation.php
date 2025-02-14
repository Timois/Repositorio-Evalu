<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    protected $table = 'evaluations';

    public function academicManagementPeriod()
    {
        return $this->belongsTo(AcademicManagementPeriod::class, 'academic_management_period_id');
    }
   public function students_test():HasMany{
       return $this->hasMany(StudentTest::class);
   }

   public function areaScores()
   {
       return $this->hasMany(EvaluationAreaScore::class);
   }

   public function questionEvaluations():HasMany{
       return $this->hasMany(QuestionEvaluation::class);
   }
}