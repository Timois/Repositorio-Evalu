<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AcademicManagementCareer extends Model
{
    protected $table = "academic_management_career";

    public function academicManagemnt():BelongsToMany{
        return $this->belongsToMany(AcademicManagement::class);
    }
    public function academicManagementPeriod() : BelongsTo {
        return $this->belongsTo(AcademicManagementPeriod::class);
    }
}
