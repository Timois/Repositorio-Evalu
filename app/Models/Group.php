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
        'name',
        'description',
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
    public function lab()
    {
        return $this->belongsTo(Laboratorie::class, 'laboratory_id', 'id');
    }
}
