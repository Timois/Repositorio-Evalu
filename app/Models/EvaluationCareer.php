<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationCareer extends Model
{
    protected $table = 'evaluation_career';

    public function evaluation():BelongsTo {
        return $this->belongsTo(Evaluation::class);
    }

    public function academic_management_period():BelongsTo {
        return $this->belongsTo(AcademicManagementPeriod::class);
    }
}
