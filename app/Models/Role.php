<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasPermissions;

class Role extends Model
{
    use HasFactory;
    use HasPermissions;

    protected $table = 'roles';
    protected $fillable = [
        'name',
        'guard_name', 
    ];
}
