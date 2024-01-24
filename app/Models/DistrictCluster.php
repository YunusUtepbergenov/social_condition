<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistrictCluster extends Model
{
    use HasFactory;

    protected $table = 'district_clusters';

    public function district(){
        return $this->belongsTo(District::class,'district_code', 'code');
    }

    public function above(){
        return $this->belongsTo(Cluster::class, 'cluster_id', 'id');
    }
}
