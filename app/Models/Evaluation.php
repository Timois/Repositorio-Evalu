<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    protected $table = 'evaluations';

   public function period_career(): BelongsTo{
       return $this->belongsTo(AcademicManagementPeriod::class);
   }
   public function students_test():HasMany{
       return $this->hasMany(StudentTest::class);
   }
   public function rules_test():HasMany{
       return $this->hasMany(RuleTest::class);
   }

   public function areaScores()
   {
       return $this->hasMany(EvaluationAreaScore::class);
   }
}