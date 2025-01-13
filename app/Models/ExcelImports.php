<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcelImports extends Model
{
    protected $table = "excel_imports";

    public function excel_imports_detail():BelongsTo{
        return $this->belongsTo(ExcelImportsDetail::class);
    }
}
