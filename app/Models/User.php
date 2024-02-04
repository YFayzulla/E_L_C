<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'password',
        'passport',
        'date_born',
        'location',
        'parents_name',
        'parents_tel',
        'group_id',
        'photo',
        'should_pay',
        'description',
        'status',
    ];


    public function teacherhasGroup(){
        return $this->hasMany(Group::class);
    }

    public function group(){
        return $this->belongsTo(Group::class);
    }

    public function studentinformation(){
        return $this->hasMany(StudentInformation::class);
//        return $query->StudentInformation::where('user_id','id')->first();
    }

    public function studenthistory(){
        return $this->hasMany(HistoryPayments::class);
    }

    public function assessment(){
        return $this->hasMany(Assessment::class );
    }

    public function studentdept(){
        return $this->hasOne(DeptStudent::class,'user_id','id' );
    }

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
}
