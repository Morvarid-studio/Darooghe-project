<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasApiTokens, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_name',
        'email',
        'password',
        'phone',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_completed',
        'profile_accepted',
        'role',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'phone' => 'string',
        ];
    }


    /**
     * بررسی اینکه آیا کاربر Admin است یا نه
     */
    public function isAdmin(): bool
    {
        if (!$this->relationLoaded('roleRelation')) {
            $this->load('roleRelation');
        }
        return $this->roleRelation && $this->roleRelation->name === 'admin';
    }

    /**
     * Get role name as string (for JSON serialization)
     */
    public function getRoleAttribute(): ?string
    {
        if (!$this->relationLoaded('roleRelation')) {
            $this->load('roleRelation');
        }
        return $this->roleRelation?->name;
    }

    /**
     * Get role relation (to avoid conflict with accessor)
     */
    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Get profile_completed from View (based on active information record)
     */
    /**
     * Get profile_completed from View (based on active information record)
     */
    public function getProfileCompletedAttribute(): bool
    {
        $status = DB::table('user_profile_status')
            ->where('user_id', $this->id)
            ->first();
        
        return $status ? (bool) $status->profile_completed : false;
    }

    /**
     * Get profile_accepted from View (based on active information record)
     */
    public function getProfileAcceptedAttribute(): bool
    {
        $status = DB::table('user_profile_status')
            ->where('user_id', $this->id)
            ->first();
        
        return $status ? (bool) $status->profile_accepted : false;
    }

    public function workLogs()
    {
        return $this->hasMany(WorkLog::class);
    }
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    // Alias برای سازگاری با کد قدیمی
    public function bankInfo()
    {
        return $this->accounts();
    }

    public function information()
    {
        return $this->hasMany(Information::class);
    }
}
