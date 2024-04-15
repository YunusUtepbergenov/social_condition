<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sentiment_Question extends Model
{
    use HasFactory;

    protected $table = 'pb_question_region';
}
