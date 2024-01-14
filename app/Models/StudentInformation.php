<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentInformation extends Model
{
    use HasFactory;
    protected $fillable=['user_id','group_id','overall_result','level','description','money_status'];

    public function group(){
        return Group::where('id','level')->first();
    }
}
