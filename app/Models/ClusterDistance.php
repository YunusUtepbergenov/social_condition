<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClusterDistance extends Model
{
    use HasFactory;

    protected $table = 'cluster_distance';

    public function district(){
        return $this->belongsTo(District::class, 'district_code', 'code');
    }
}