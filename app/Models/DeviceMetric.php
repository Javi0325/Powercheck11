<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceMetric extends Model
{
    protected $fillable = ['device_id', 'athlete_id', 'bpm', 'repeticiones'];
}