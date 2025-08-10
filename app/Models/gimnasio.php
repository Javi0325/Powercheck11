<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class gimnasio extends Model
{
    protected $table = 'gimnasios';
    protected $fillable = [
        'nombre',
        'ubicacion',
        'celular',
        'logo',

    ];
    public function entrenadors()
    {
        return $this->hasMany(entrenador::class);
    }
}
