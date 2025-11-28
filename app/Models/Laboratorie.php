<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laboratorie extends Model
{
    protected $table = 'laboratories';
    protected $fillable = [
        'name',
        'location',
        'equipment_count',
    ];

    // Relación con grupos
    public function groups()
    {
        return $this->hasMany(Group::class, 'laboratory_id', 'id');
    }
    public function career()
    {
        return $this->belongsTo(Career::class);
    }
}
