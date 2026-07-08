<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'status',
    ];

    /**
     * Los atributos que deben ocultarse para la serialización.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Los atributos que deben ser casteados.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Obtener las fincas asociadas a este usuario.
     */
    public function fincas()
    {
        return $this->belongsToMany(Finca::class, 'finca_user')
            ->withPivot(['access_level', 'is_default', 'status'])
            ->withTimestamps();
    }

    /**
     * Obtener las personas asociadas a este usuario.
     */
    public function personas()
    {
        return $this->belongsToMany(Persona::class, 'persona_user')
            ->withTimestamps();
    }

    /**
     * Obtener los roles asociados a este usuario.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Verificar si el usuario es administrador.
     */
    public function isAdmin(): bool
    {
        return $this->roles()->whereIn('code', ['admin', 'global_admin'])->exists();
    }

    /**
     * Verificar si el usuario es un propietario.
     */
    public function isPropietario(): bool
    {
        return $this->roles()->where('code', 'propietario')->exists();
    }

    /**
     * Verificar si el usuario es un técnico.
     */
    public function isTecnico(): bool
    {
        return $this->roles()->where('code', 'tecnico')->exists();
    }

    /**
     * Obtener el tipo de usuario (para retrocompatibilidad con frontend).
     */
    public function getTypeUserAttribute(): string
    {
        $role = $this->roles->first();
        if ($role) {
            return $role->code === 'global_admin' ? 'admin' : $role->code;
        }
        return 'tecnico';
    }


    /**
     * Obtener la imagen de perfil (para retrocompatibilidad).
     */
    public function getImageAttribute(): string
    {
        return 'user.png';
    }

    /**
     * Obtener el propietario asociado a través de la relación personas (si existe).
     */
    public function getPropietarioAttribute()
    {
        $persona = $this->personas()->first();
        return $persona ? $persona->propietario : null;
    }
}

