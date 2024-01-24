<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProtestPrediction extends Model
{
    use HasFactory;

    protected $table = 'protest_predictions';

    public function district(){
        return $this->belongsTo(District::class, 'district_code', 'code');
    }
}
