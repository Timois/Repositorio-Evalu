<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationAreaScore extends Model
{
    protected $table = 'evaluation_area_scores';

    public function area()
    {
        return $this->belongsTo(Areas::class);
    }
    
}
