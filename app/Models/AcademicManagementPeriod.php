<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicManagementPeriod extends Model
{
    protected $table = "academic_management_period";

    public function period():BelongsTo{
        return $this->belongsTo(Period::class);
    }
    public function academicManagementCareer():HasMany{
        return $this->hasMany(AcademicManagementCareer::class);
    }
    public function periodExtension():HasMany{
        return $this->hasMany(PeriodExtension::class);
    }
}
