<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceSession extends Model
{
    protected $fillable = ['device_id', 'athlete_id', 'status', 'started_at', 'ended_at', 'expires_at'];
    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime', 'expires_at' => 'datetime'];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}