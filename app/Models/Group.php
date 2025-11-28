<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Group extends Model
{
    protected $table = 'groups';
    protected $fillable = [
        'evaluation_id',
        'laboratory_id',
        'name',
        'description',
        'start_time',
        'end_time',
        'total_students',
    ];
    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class)->withTimestamps();
    }
    /*******  72512fe0-a4bc-4a4d-a30f-8cebeb7a7d06  *******/ /*************  ✨ Windsurf Command ⭐  *************/
    public function lab()
    {
        return $this->belongsTo(Laboratorie::class, 'laboratory_id', 'id');
    }
}
