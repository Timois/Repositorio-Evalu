<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $table = "units";
    // Relacion con carreras
    public function careers():HasMany{
        return $this->hasMany(Career:: class);
    }
}   
