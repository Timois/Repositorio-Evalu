<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupGenerateQuestion extends Model
{
    protected $table = 'backup_of_generate_questions'; // Nombre de la tabla

    protected $fillable = [
        'evaluation_id',
        'areas_selected',
        'questions_generated',
        'scores_asigned',
    ];

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }
}
