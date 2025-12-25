<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveStudent extends Model
{
    use HasFactory;
    protected $fillable = ['student_id', 'status'];
}
