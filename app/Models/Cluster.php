<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cluster extends Model
{
    use HasFactory;

    public function clusters(){
        return $this->hasMany(DistrictCluster::class, 'cluster_id', 'id');
    }

    public function ntl(){
        return $this->hasMany(NtlData::class, 'cluster_ascending', 'id');
    }
}
