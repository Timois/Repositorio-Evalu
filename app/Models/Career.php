<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Career extends Model
{
    protected $table = "careers";

    public function academic_management():BelongsToMany{
        return $this->belongsToMany(AcademicManagement::class)
            ->withTimestamps();
    }
    public function unit():BelongsTo{
        return $this->belongsTo(Unit::class);
    }

}
