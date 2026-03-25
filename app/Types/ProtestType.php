<?php

namespace App\Types;

use App\Abstracts\DataType;
use App\Models\Merged;
use App\Models\MiProtest;
use App\Models\Protest;
use App\Models\ProtestPrediction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProtestType extends DataType
{
    public function getTopDistricts(string $activeRegion, ?string $activeIndicator, string $date): Collection
    {
        if ($activeRegion == 'republic') {
            return ProtestPrediction::with('district')->select(['district_code', 'prediction as score'])->where('date', $date)->orderByRaw('score DESC nulls last')->get();
        }

        return ProtestPrediction::with('district')
            ->select(['district_code', 'prediction as score'])
            ->where('date', $date)
            ->where('district_code', 'LIKE', $activeRegion . '%')
            ->orderByRaw('score DESC nulls last')
            ->get();
    }

    public function getIndicators(string $tuman, string $date, int $population, int $tum_pop, array $avg_indicators): Collection
    {
        $indicators = MiProtest::select('feature_name')->where('district_code', $tuman)->whereDate('date', $date)->orderBy('mutual_info', 'DESC')->get();

        return $indicators->map(function ($indicator) use ($tuman, $date, $population, $tum_pop, $avg_indicators) {
            if (in_array($indicator->feature_name, $avg_indicators)) {
                $indicator->average = Merged::select(DB::raw('AVG(' . $indicator->feature_name . ') as avg'))->whereDate('date', $date)->groupBy('date')->first()?->avg;
                $indicator->value = Merged::select($indicator->feature_name . ' as indicator')->whereDate('date', $date)->where('district_code', $tuman)->first()?->indicator;
            } else {
                $sumResult = Merged::select(DB::raw('SUM(' . $indicator->feature_name . ') as sum'))->whereDate('date', $date)->groupBy('date')->first();
                $indicator->average = ($population > 0 && $sumResult) ? ($sumResult->sum / $population) * 100000 : null;
                $valResult = Merged::select($indicator->feature_name . ' as indicator')->whereDate('date', $date)->where('district_code', $tuman)->first();
                $indicator->value = ($tum_pop > 0 && $valResult) ? ($valResult->indicator / $tum_pop) * 100000 : null;
            }
            return $indicator;
        });
    }

    public function getRegionPredicts(string $region, string $date): array
    {
        return ProtestPrediction::select('date', DB::raw('AVG(prediction) as average'))
            ->where('date', '<=', $date)
            ->where('district_code', 'LIKE', $region . '%')
            ->groupBy('date')->orderBy('date')
            ->get()
            ->pluck('average')
            ->toArray();
    }

    public function getRegionData(string $region, string $date): array
    {
        return Protest::select('date', DB::raw('SUM(count) as average'))
            ->where('date', '<=', $date)
            ->where('district_code', 'LIKE', $region . '%')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('average')
            ->toArray();
    }

    public function getRegionParticipants(string $region, string $date): array
    {
        return Protest::select('date', DB::raw('SUM(participants) as score'))
            ->where('date', '<=', $date)
            ->where('district_code', 'LIKE', $region . '%')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('score')
            ->toArray();
    }
}
