<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicManagementCareer extends Model
{
    protected $table = "academic_management_career";

    public function academicManagement(): BelongsTo {
        return $this->belongsTo(AcademicManagement::class);
    }

    public function career(): BelongsTo {
        return $this->belongsTo(Career::class);
    }

    public function academicManagementPeriod(): HasMany {
        return $this->hasMany(AcademicManagementPeriod::class);
    }
}
