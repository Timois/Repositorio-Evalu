<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Areas extends Model
{
    protected $table = "areas";

    protected $fillable = [
        'name',
        'description',
    ];
    public function bank_questions():BelongsTo{
        return $this->belongsTo(QuestionBank::class);
    }
}
