<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'wa_phone',
        'password',
        'role_code',
        'entity_group',
        'yayasan_mode',
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
        ];
    }

    public function isChecker(): bool
    {
        return in_array($this->role_code, ['CHECKER_ORG', 'CHECKER_YAYASAN']);
    }

    public function isApprover(): bool
    {
        return $this->role_code === 'PEMBERI_ANGGARAN'
            || ($this->role_code === 'YAYASAN' && $this->yayasan_mode === 'FUND_PROVIDER');
    }

    public function isKu(): bool
    {
        return $this->role_code === 'KU';
    }

    public function isApplicant(): bool
    {
        return in_array($this->entity_group, ['MAIN_ENTITY', 'SCHOOL'])
            && !($this->role_code === 'YAYASAN' && $this->yayasan_mode === 'FUND_PROVIDER');
    }

    public function isAdmin(): bool
    {
        return $this->role_code === 'ADMIN';
    }
}