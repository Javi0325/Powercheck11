<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\rutina_sets;

class ejercicio extends Model
{
    //
    protected $table = 'ejercicios';
    protected $fillable = ['nombre', 'descripcion'];
    public function sets()
    {
        return $this->hasMany(rutina_sets::class, 'ejercicio_id');
    }
}
