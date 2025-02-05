<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    protected $table = 'evaluations';

   public function evaluation_career(): HasMany{
       return $this->hasMany(EvaluationCareer::class);
   }
   public function student():HasMany{
       return $this->hasMany(Student::class);
   }
}