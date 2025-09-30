<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Result extends Model
{
    protected $table = 'results';
    protected $fillable = [
        'student_test_id',
        'qualification',
        'exam_duration',
        'status',
    ];

    public function studentTest()
    {
        return $this->belongsTo(StudentTest::class);
    }
}
