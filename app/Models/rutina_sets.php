<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class rutina_sets extends Model
{
    //
    protected $table = 'rutina_sets';

    protected $fillable = [
        'rutina_id',
        'ejercicio_id',
        'semana',
        'dia_semana',
        'orden',
        'series',
        'repeticiones',
        'peso_kg',
        'rpe',
        'notas',
    ];

    protected function repeticiones(): Attribute
    {
        return Attribute::make(
            set: fn($value) =>
            is_string($value) && strcasecmp(trim($value), 'amrap') === 0
                ? 0
                : (int) $value
        );
    }

    // Mostrar "AMRAP" si es 0
    public function getRepeticionesLabelAttribute(): string
    {
        return $this->repeticiones === 0 ? 'AMRAP' : (string) $this->repeticiones;
    }

    // Relaciones
    public function rutina()
    {
        return $this->belongsTo(rutinas::class, 'rutina_id');
    }

    public function ejercicio()
    {
        return $this->belongsTo(Ejercicio::class, 'ejercicio_id');
    }
}
