<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Career::class);
    }
}
