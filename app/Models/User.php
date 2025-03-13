<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'email',
        'username',
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime:UTC',
            'password' => 'hashed',
            'last_login' => 'datetime:UTC',
            'created_at' => 'datetime:UTC',
            'updated_at' => 'datetime:UTC',
        ];
    }

    /**
     * Pastikan waktu selalu dalam UTC saat diambil atau disimpan
     */
    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Carbon::parse($value)->setTimezone('UTC'),
            set: fn($value) => Carbon::parse($value)->setTimezone('UTC')
        );
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Carbon::parse($value)->setTimezone('UTC'),
            set: fn($value) => Carbon::parse($value)->setTimezone('UTC')
        );
    }
}
