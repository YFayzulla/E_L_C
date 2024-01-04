<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
//        'plan_id',
        'address',
        'phone',
        'url',
        'latitude',
        'longitude',
        'status',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clinic_has_doctor(){
        return $this->hasMany(Doctor::class);
    }

//    public function plan()
//    {
//        return $this->belongsTo(Plan::class, 'plan_id');
//    }
}
