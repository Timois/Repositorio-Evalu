<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserStudent extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'students';
    protected $guard_name = 'api';
    protected $fillable = [
        'ci',
        'birthdate',
        'password',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function generarPassword()
    {
        // Eliminar cualquier carácter que no sea un número (maneja d/m/Y y d-m-Y)
        $fechaNumerica = preg_replace('/\D/', '', $this->birthdate);
        // Concatenar con la cédula de identidad
        return $this->ci . $fechaNumerica;
    }


    // Métodos requeridos por JWT
    public function getJWTIdentifier()
    {
        // Devuelve el id del usuario
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'name' => $this->name,
        ];
    }

}
