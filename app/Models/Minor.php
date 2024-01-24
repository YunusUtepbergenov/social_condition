<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Minor extends Model
{
    use HasFactory;

    protected $guarded = [
        'id'
    ];

    public function district(){
        return $this->belongsTo(District::class, 'district_code', 'code');
    }
}
