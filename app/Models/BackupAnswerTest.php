<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupAnswerTest extends Model
{
    protected $table = 'backup_answers_test'; // Nombre de la tabla
    protected $fillable = [
        'student_test_id',
        'question_id',
        'answer_id',
        'time',
    ];
    public function studentTest():BelongsTo
    {
        return $this->belongsTo(StudentTest::class, 'student_test_id');
    }
}
