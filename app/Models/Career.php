<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Career extends Model
{
    protected $table = "careers";

    protected $fillable = [
        'name',
        'initials',
        'logo',
        'type',
        'unit_id'
    ];

    // Constantes para los tipos
    const TYPE_MAYOR = 'mayor';
    const TYPE_FACULTAD = 'facultad';
    const TYPE_CARRERA = 'carrera';
    const TYPE_DEPENDIENTE = 'dependiente';

    // Tipos que son independientes (no tienen padre)
    const INDEPENDENT_TYPES = [self::TYPE_MAYOR, self::TYPE_FACULTAD];
    
    // Tipos que deben tener un padre
    const DEPENDENT_TYPES = [self::TYPE_CARRERA, self::TYPE_DEPENDIENTE];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($career) {
            // Si es tipo independiente (mayor o facultad)
            if (in_array($career->type, self::INDEPENDENT_TYPES)) {
                $career->unit_id = 0;
            } 
            // Si es tipo dependiente (carrera o dependiente)
            elseif (in_array($career->type, self::DEPENDENT_TYPES)) {
                if ($career->unit_id === 0 || 
                    !self::where('id', $career->unit_id)
                         ->whereIn('type', self::INDEPENDENT_TYPES)
                         ->exists()) {
                    throw new \Exception('Una carrera o dependiente debe pertenecer a una facultad o mayor válido.');
                }
            }
        });
    }

    // Relación con la unidad padre
    public function parentUnit(): BelongsTo
    {
        return $this->belongsTo(Career::class, 'unit_id');
    }

    // Relación con unidades dependientes
    public function dependentUnits(): HasMany
    {
        return $this->hasMany(Career::class, 'unit_id');
    }

    // Scopes para cada tipo
    public function scopeMayor($query)
    {
        return $query->where('type', self::TYPE_MAYOR);
    }

    public function scopeFacultad($query)
    {
        return $query->where('type', self::TYPE_FACULTAD);
    }

    public function scopeCarrera($query)
    {
        return $query->where('type', self::TYPE_CARRERA);
    }

    public function scopeDependiente($query)
    {
        return $query->where('type', self::TYPE_DEPENDIENTE);
    }

    // Scope para unidades independientes
    public function scopeIndependent($query)
    {
        return $query->whereIn('type', self::INDEPENDENT_TYPES);
    }

    // Scope para unidades dependientes
    public function scopeDependent($query)
    {
        return $query->whereIn('type', self::DEPENDENT_TYPES);
    }

    // Obtener unidades por tipo de padre
    public function scopeByParentType($query, $parentType)
    {
        return $query->whereHas('parentUnit', function($q) use ($parentType) {
            $q->where('type', $parentType);
        });
    }

    // Verificar si es una unidad independiente
    public function isIndependent(): bool
    {
        return in_array($this->type, self::INDEPENDENT_TYPES);
    }

    public function academic_management():BelongsToMany{
        return $this->belongsToMany(AcademicManagement::class)
            ->withTimestamps();
    }

    public function areas():HasMany{
        return $this->hasMany(Areas::class);
    }
}
