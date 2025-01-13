<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExcelImportsDetail extends Model
{
    protected $table = "excel_imports_detail";

    public function excel_imports():HasMany{
        return $this->hasMany(ExcelImports::class);
    }
}
