<?php

namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
   
    protected $table = 'users';
    protected $fillable = [
        'name',
        'email',    
        'password',
        'role',
        'career_id', // Asegúrate de incluir este campo si lo vas a asignar
    ];

    /**
     * Los atributos que deben estar ocultos para la serialización.
     *
     * @var array
     */
    protected $hidden = [
        'password', // Oculta la contraseña al serializar el modelo
    ];

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Career::class);
    }

    // Métodos requeridos por JWT
    public function getJWTIdentifier()
    {
        // Devuelve el id del usuario
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
