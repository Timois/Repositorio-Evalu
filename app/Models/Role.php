<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    // No necesitas usar HasPermissions aquí, ya lo trae SpatieRole
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'guard_name',
    ];
}

