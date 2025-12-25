<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryPayments extends Model
{

    use HasFactory;

    protected $fillable=['user_id','name','payment','group','date','type_of_money'];

    // Primary relation to the payer (student user)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Backward-compatible alias used in some views/code
    public function student()
    {
        return $this->user();
    }

}
