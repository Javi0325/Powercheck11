<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'devices'; // nombre de la tabla, opcional si sigue convención
    protected $fillable = ['name', 'ip', 'status', 'last_seen'];
}