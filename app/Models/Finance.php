<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Finance extends Model
{
    use HasFactory;

    protected $fillable = [
        'reason', 'payment', 'type', 'status'
    ];

    public const CASH = 1;
    public const CARD = 2;

//    public const STATUS_TEACHER = 1;
//    public const STATUS_OTHER = 2;
//    public const STATUS_INCOME = 3;

    public function getTypeNameAttribute()
    {
        return $this->type == self::CASH ? 'Cash' : 'Card';
    }

}
