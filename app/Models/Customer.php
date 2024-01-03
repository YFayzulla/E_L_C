<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => 0,
    ];
    protected $fillable = [
        'name',
        'phone',
        'doctor_id',
        'language',
        'date',
        'time',
        'end_time',
        'procedura',
        'status',
    ];
    public $timestamps = false;
}
