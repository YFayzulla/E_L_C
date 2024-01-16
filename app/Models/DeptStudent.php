<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeptStudent extends Model
{
    use HasFactory;
    protected $fillable=['user_id','should_pay','payed','status','date'];

    public $timestamps = false;



}
