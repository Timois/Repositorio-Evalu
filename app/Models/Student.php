<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected $table = 'students';

    protected $fillable = [
        'ci',
        'name',
        'paternal_surname',
        'maternal_surname',
        'phone_number',
        'birthdate',
        'password'
    ];

    public function evaluations():BelongsTo {
        return $this->belongsTo(Evaluation::class);
    }
}
