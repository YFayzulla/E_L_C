<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentStudent extends Model
{
    use HasFactory;

    protected $fillable = ['comment', 'student_id','teacher'];
}
