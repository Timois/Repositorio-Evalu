<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Period extends Model
{
    protected $table = "periods";
    // Relacion con la tabla Management
    public function academicManagementPeriod():HasMany{
        return $this->hasMany(AcademicManagementPeriod::class);
    }
}
