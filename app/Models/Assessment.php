<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{

    use HasFactory;
    protected $fillable=['user_id','get_mark','group','overall_result','for_what','rec_group'];

    public function student(){
        return $this->belongsTo(User::class , 'user_id','id' );
    }

}
