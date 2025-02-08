<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuleTest extends Model
{
    protected $table = 'rules_tests';
    
    public function evaluations():BelongsTo{
        return $this->belongsTo(Evaluation::class);
    }

    public function gaussCurvature():HasMany{
        return $this->hasMany(GaussianCurvature::class);
    }
}
