<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingEmployeeRegistration extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'department',
        'position',
        'otp_hash',
        'otp_expires_at',
        'verified_at',
        'password_hash',
    ];

    protected $hidden = [
        'otp_hash',
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'otp_expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}