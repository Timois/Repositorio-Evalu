<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Areas extends Model
{
    protected $table = "areas";

    protected $fillable = [
        'name',
        'description',
        'career_id',
    ];
    public function bank_questions():HasMany{
        return $this->hasMany(QuestionBank::class);
    }

    public function career():BelongsTo{
        return $this->belongsTo(Career::class);
    }
}
