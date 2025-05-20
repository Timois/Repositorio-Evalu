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
    public function evaluation():BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }
}
