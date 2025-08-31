<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Device extends Model
{
    protected $table = 'devices';
    protected $fillable = ['name', 'ip', 'status', 'last_seen'];
    protected $casts = ['last_seen' => 'datetime'];

    public function sessions()
    {
        return $this->hasMany(DeviceSession::class);
    }

    public function activeSession()
    {
        return $this->hasOne(DeviceSession::class)->where('status', 'active');
    }

    // Disponible si el último ping fue hace <= 30s
    public function getIsAvailableAttribute(): bool
    {
        return $this->last_seen && $this->last_seen->gt(Carbon::now()->subSeconds(30));
    }

    public function getAssignedAthleteIdAttribute(): ?int
    {
        return optional($this->activeSession()->first())->athlete_id;
    }
   
}