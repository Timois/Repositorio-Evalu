<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicManagement extends Model
{
    protected $table = "academic_management";

    
    public function academicManagementCareer():BelongsToMany{
        return $this->belongsToMany(AcademicManagementCareer::class)
        ->withTimestamps();
    }
}
