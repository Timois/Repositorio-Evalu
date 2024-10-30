<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodExtension extends Model
{
    protected $table = "period_extensions";

    public function academicManagementPeriod():BelongsTo{
        return $this->belongsTo(AcademicManagementPeriod::class);
    }
}
