<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonAndHistory extends Model
{
    use \App\Models\Concerns\BelongsToCentre;

    use HasFactory;

    protected $fillable = [
        'name',
        'data',
        'group'
    ];
}
