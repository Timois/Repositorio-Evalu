<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GaussianCurvature extends Model
{
    protected $table = 'gaussian_curvature';

    public function rule_test():BelongsTo{
        return $this->belongsTo(RuleTest::class);
    }
}
