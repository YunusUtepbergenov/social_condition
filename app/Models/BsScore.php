<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BsScore extends Model
{
    use HasFactory;

    protected $table = 'bs_scores';

    public function district(){
        return $this->belongsTo(District::class);
    }
}
