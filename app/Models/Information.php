<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Information extends Model
{
    use HasFactory;

    protected $table = 'information'; // چون جدول جمع نیست، بهتر است نام جدول را مشخص کنیم

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'address',
        'birthday',
        'gender',
        'military',
        'degree',
        'phone',
        'emergency_contact_info',
        'emergency_contact_number',
        'education_status',
        'marital_status',
        'resume',
        'profile_photo',
        'profession',
        'languages',
    ];

    // ارتباط با مدل User (کاربر)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
