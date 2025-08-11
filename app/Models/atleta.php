<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class atleta extends Model
{
    protected $table = 'atletas';
    protected $fillable = [
        'user_id',
        'entrenador_id',
        'gimnasio_id',
        'foto',
        'fecha_nacimiento',
        'genero',
        'altura',
        'estilo_vida',
        'lesiones_previas',
    ];
    protected $casts = [
        'fecha_nacimiento' => 'date',
        'altura' => 'decimal:2',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function entrenador()
    {
        return $this->belongsTo(Entrenador::class);
    }
    public function gimnasio()
    {
        return $this->belongsTo(Gimnasio::class);
    }
}
