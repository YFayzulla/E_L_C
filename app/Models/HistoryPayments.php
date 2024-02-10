<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryPayments extends Model
{

    use HasFactory;

    protected $fillable=['user_id','name','payment','group','date','type_of_money'];

    public function student(){
        return $this->belongsTo(User::class,'id');
    }

}
