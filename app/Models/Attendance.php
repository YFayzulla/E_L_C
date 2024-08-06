<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable=['user_id','group_id','who_checked','status'];

    public function group(){
        return $this->belongsTo(Group::class,'group_id');
    }
    public function Teacher(){
        return $this->belongsTo(User::class,'who_checked');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
