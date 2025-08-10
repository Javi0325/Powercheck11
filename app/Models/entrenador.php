<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class entrenador extends Model
{
    protected $table = 'entrenadors';
    protected $fillable = [
        'user_id',
        'gimnasio_id',
        'foto',
        'especialidad',
        'experiencia',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasio::class);
    }
}
