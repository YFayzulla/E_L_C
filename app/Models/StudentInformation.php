<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentInformation extends Model
{
    use HasFactory;
    protected $fillable=['user_id','group_id','group'];

    public function groups(){
        return $this->belongsTo(Group::class,'group_id');
    }
    public function student(){
        return $this->belongsTo(User::class,'user_id','id');
    }
}
