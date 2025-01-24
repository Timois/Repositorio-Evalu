<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExcelImports extends Model
{
    protected $table = "excel_imports";

    protected $fillable = [
        'file_name', 
        'size', 
        'status', 
        'file_path', 
        'file_hash'
    ];
    public function bank_questions():BelongsTo{
        return $this->belongsTo(QuestionBank::class);
    }
}
