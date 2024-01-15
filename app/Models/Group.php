<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;
    protected $fillable=['name','beginning','start_time','finish_time','level','monthly_payment'];

    public function teacherhasGroup(){
        return $this->hasMany(User::class);
    }

    public function level(){
        return Level::where('id','level');
    }
}
