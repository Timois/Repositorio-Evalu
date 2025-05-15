<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class ShowPermissionsByGuard extends Command
{
    protected $signature = 'permissions:list';

    protected $description = 'Lista todos los permisos y sus guards';

    public function handle()
    {
        $permissions = Permission::all();

        if ($permissions->isEmpty()) {
            $this->warn("No hay permisos registrados.");
            return;
        }

        $this->table(
            ['ID', 'Nombre', 'Guard Name'],
            $permissions->map(function ($perm) {
                return [
                    $perm->id,
                    $perm->name,
                    $perm->guard_name,
                ];
            })
        );
    }
}
