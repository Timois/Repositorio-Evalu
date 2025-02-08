<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalResult extends Model
{
    protected $table = 'final_results';

    public function draft_exam_result():BelongsTo{
        return $this->belongsTo(DraftExamResult::class);
    }
}
