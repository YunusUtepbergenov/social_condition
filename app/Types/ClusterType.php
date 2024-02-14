<?php

namespace App\Types;

use App\Abstracts\DataType;
use App\Models\DistrictCluster;
use Illuminate\Support\Facades\DB;

class ClusterType extends DataType{
    public function getTopDistricts($activeRegion, $activeIndicator, $date){
        if($activeRegion == 'republic')
                return DistrictCluster::with('district')->select(['district_code', 'cluster_id as score'])->where('date', $date)->orderBy('score')->get();
        else
            return DistrictCluster::with('district')
                    ->select(['district_code', DB::raw('cluster_id as score')])
                    ->where('date', $date)
                    ->where('district_code', 'LIKE', $activeRegion.'%')
                    ->orderByRaw('score DESC nulls last')
                    ->get();
    } 
}