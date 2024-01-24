<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Merged extends Model
{
    use HasFactory;

    protected $table = 'merged';

    public function district(){
        return $this->belongsTo(District::class);
    }
}
