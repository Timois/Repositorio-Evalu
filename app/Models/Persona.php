<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
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
}
