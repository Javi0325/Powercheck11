<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Atleta;
use App\Models\rutina_sets;

class rutinas extends Model
{
    //
    protected $table = 'rutinas';

    protected $fillable = [
        'entrenador_id',
        'atleta_id',
        'nombre',
        'objetivo',
        'dias_por_semana',
        'duracion_semanas',
        'version',
        'semana_actual',
        'dia_actual',
        'ultimo_dia_completado_at',
    ];

    // Relaciones
    public function entrenador()
    {
        return $this->belongsTo(User::class, 'entrenador_id');
    }

    public function atleta()
    {
        return $this->belongsTo(Atleta::class, 'atleta_id');
    }

    public function sets()
    {
        return $this->hasMany(rutina_sets::class, 'rutina_id')
            ->orderBy('semana')
            ->orderBy('dia_semana')
            ->orderBy('orden');
    }

    // Es plantilla si no tiene atleta asignado
    public function getEsPlantillaAttribute(): bool
    {
        return $this->atleta_id === null;
    }

    // Avanzar al siguiente día
    public function avanzarDia(): void
    {
        $this->ultimo_dia_completado_at = now();

        if ($this->dia_actual < $this->dias_por_semana) {
            $this->dia_actual++;
        } else {
            $this->dia_actual = 1;
            $this->semana_actual++;
        }

        if ($this->semana_actual > $this->duracion_semanas) {
            // rutina terminada
            $this->semana_actual = $this->duracion_semanas;
            $this->dia_actual = $this->dias_por_semana;
        }

        $this->save();
    }
}
