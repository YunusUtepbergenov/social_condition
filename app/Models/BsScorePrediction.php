<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BsScorePrediction extends Model
{
    use HasFactory;

    protected $table = 'bs_scores_prediction';

    public function district(){
        return $this->belongsTo(District::class, 'district_code', 'code');
    }
}
