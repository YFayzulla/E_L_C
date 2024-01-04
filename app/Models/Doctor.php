<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;
    protected $fillable=[
        'name',
        'surname',
        'passport',
        'phone',
        'password',
        'image',
        'room_id',
        'status',
        ];

    public function clinic_has_doctor(){
        return $this->hasMany(Clinic::class);
    }
}
