<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ActionType;
use App\Enums\UserType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * App\Models\User
 * @property int $role
*/

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getRoleNameAttribute()
    {
        return UserType::getKeys($this->role)[0];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier() {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    }

    public static function boot(): void
    {
        parent::boot();
        self::created(function ($model) {
            DB::table('user_histories')->insert([
                'user_id' => $model->id,
                'name' => $model->name,
                'email' => $model->email,
                'password' => $model->password,
                'phone' => $model->phone,
                'role' => $model->role,
                'status' => ActionType::CREATE,
                'created_by' => auth()->user()->id ?? 'system'
            ]);
        });
        self::updated(function ($model) {
            DB::table('user_histories')->insert([
                'user_id' => $model->id,
                'name' => $model->name,
                'email' => $model->email,
                'password' => $model->password,
                'phone' => $model->phone,
                'role' => $model->role,
                'status' => ActionType::UPDATE,
                'created_by' => auth()->user()->id ?? 'system'
            ]);
        });
        self::deleting(function ($model) {
            DB::table('user_histories')->insert([
                'user_id' => $model->id,
                'name' => $model->name,
                'email' => $model->email,
                'password' => $model->password,
                'phone' => $model->phone,
                'role' => $model->role,
                'status' => ActionType::DELETE,
                'created_by' => auth()->user()->id ?? 'system'
            ]);
        });
    }
}
