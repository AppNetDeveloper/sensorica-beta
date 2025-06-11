<?php

namespace App\Models;

use App\Facades\UtilityFacades;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','type'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'name'=>'string'
    ];
    
    /**
     * Asigna el rol "user" inmediatamente después de crear al usuario.
     */
    protected static function booted()
    {
        static::created(function ($user) {
            // Asignar rol por nombre (asegúrate de que el rol "user" ya exista)
            $user->assignRole('user');
        });
    }
    public function creatorId()
    {

        if($this->type == 'company' || $this->type == 'admin')
        {
            return $this->id;
        }
        else
        {
            return $this->created_by;
        }
    }

    public function currentLanguage()
    {
        return $this->lang;
    }

    public function loginSecurity()
    {
        return $this->hasOne('App\Models\LoginSecurity');
    }
}
