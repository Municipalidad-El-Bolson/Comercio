<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isAdmin(): bool  { return $this->role === 'admin'; }
    public function isWriter(): bool { return $this->role === 'writer'; }
    public function isReader(): bool { return $this->role === 'reader'; }
    public function isMesa(): bool { return $this->role === 'mesa'; }
}
