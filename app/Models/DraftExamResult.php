<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DraftExamResult extends Model
{
    protected $table = 'draft_exam_results';

    public function student_test():BelongsTo{
        return $this->belongsTo(Student::class);
    }

    public function final_result():HasMany{
        return $this->hasMany(FinalResult::class);
    }
        
}
