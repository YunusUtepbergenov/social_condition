<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProtestIndicator extends Model
{
    use HasFactory;

    protected $table = 'protest_indicators';

    public function district(){
        return $this->belongsTo(District::class, 'district_code', 'code');
    }
}