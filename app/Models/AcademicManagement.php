<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicManagement extends Model
{
    protected $table = "academic_management";

    public function management_extensions():HasMany{
        return $this->hasMany(ManagementExtension::class);
    }

    public function careers():BelongsToMany{
        return $this->belongsToMany(Career::class)
            ->withTimestamps();
    }
    public function academicManagementPeriod():BelongsToMany{
        return $this->belongsToMany(AcademicManagementPeriod::class)
        ->withTimestamps();
    }
}
