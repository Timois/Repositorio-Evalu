<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagementExtension extends Model
{
    protected $table = "management_extensions";

    public function academic_management():BelongsTo{
        return $this->belongsTo(AcademicManagement::class);
    }
}
