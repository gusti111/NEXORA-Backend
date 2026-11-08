<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'nim',
        'username',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function pklsAsMahasiswa(): HasMany
    {
        return $this->hasMany(Pkl::class, 'mahasiswa_id');
    }

    public function pklsAsDosen(): HasMany
    {
        return $this->hasMany(Pkl::class, 'dosen_id');
    }

    public function pklsAsKorlap(): HasMany
    {
        return $this->hasMany(Pkl::class, 'korlap_id');
    }

    public function logbookReviews(): HasMany
    {
        return $this->hasMany(LogbookReview::class, 'dosen_id');
    }
}