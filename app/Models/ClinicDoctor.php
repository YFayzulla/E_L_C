<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicDoctor extends Model
{
    use HasFactory;
    protected $fillable=['doctor_id','clinic_id'];

    public function doctor(){
        return $this->belongsTo(Doctor::class);
    }
    public function clinic(){
        return $this->belongsTo(Clinic::class);
    }

}

