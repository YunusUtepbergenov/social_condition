<?php

namespace App\Types;

use App\Abstracts\DataType;
use App\Models\DistrictCluster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClusterType extends DataType
{
    public function getTopDistricts(string $activeRegion, ?string $activeIndicator, string $date): Collection
    {
        if ($activeRegion == 'republic') {
            return DistrictCluster::with('district')->select(['district_code', 'cluster_id as score'])->where('date', $date)->orderBy('score', 'ASC')->get();
        }

        return DistrictCluster::with('district')
            ->select(['district_code', DB::raw('cluster_id as score')])
            ->where('date', $date)
            ->where(fn($q) => whereDistrictPrefix($q, $activeRegion))
            ->orderByRaw('score DESC nulls last')
            ->get();
    }
}
