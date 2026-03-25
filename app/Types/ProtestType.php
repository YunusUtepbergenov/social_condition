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
            ->where(fn($q) => whereDistrictPrefix($q, $activeRegion))
            ->orderByRaw('score DESC nulls last')
            ->get();
    }

    public function getIndicators(string $tuman, string $date, int $population, int $tum_pop, array $avg_indicators): Collection
    {
        $indicators = MiProtest::select('feature_name')->where('district_code', $tuman)->whereDate('date', $date)->orderBy('mutual_info', 'DESC')->get();

        if ($indicators->isEmpty()) {
            return $indicators;
        }

        $featureNames = $indicators->pluck('feature_name')->toArray();
        $districtRow = Merged::where('date', $date)->where('district_code', $tuman)->first();

        $avgColumns = array_intersect($featureNames, $avg_indicators);
        $sumColumns = array_diff($featureNames, $avg_indicators);

        $avgValues = [];
        if (!empty($avgColumns)) {
            $selects = array_map(fn($col) => "AVG({$col}) as {$col}", $avgColumns);
            $avgValues = (array) Merged::selectRaw(implode(', ', $selects))->where('date', $date)->groupBy('date')->first()?->getAttributes();
        }

        $sumValues = [];
        if (!empty($sumColumns)) {
            $selects = array_map(fn($col) => "SUM({$col}) as {$col}", $sumColumns);
            $sumValues = (array) Merged::selectRaw(implode(', ', $selects))->where('date', $date)->groupBy('date')->first()?->getAttributes();
        }

        return $indicators->map(function ($indicator) use ($districtRow, $avgValues, $sumValues, $population, $tum_pop, $avg_indicators) {
            $feature = $indicator->feature_name;
            if (in_array($feature, $avg_indicators)) {
                $indicator->average = $avgValues[$feature] ?? null;
                $indicator->value = $districtRow?->{$feature};
            } else {
                $sumVal = $sumValues[$feature] ?? null;
                $indicator->average = ($population > 0 && $sumVal !== null) ? ($sumVal / $population) * 100000 : null;
                $distVal = $districtRow?->{$feature};
                $indicator->value = ($tum_pop > 0 && $distVal !== null) ? ($distVal / $tum_pop) * 100000 : null;
            }
            return $indicator;
        });
    }

    public function getRegionPredicts(string $region, string $date): array
    {
        return ProtestPrediction::select('date', DB::raw('AVG(prediction) as average'))
            ->where('date', '<=', $date)
            ->where(fn($q) => whereDistrictPrefix($q, $region))
            ->groupBy('date')->orderBy('date')
            ->get()
            ->pluck('average')
            ->toArray();
    }

    public function getRegionData(string $region, string $date): array
    {
        return Protest::select('date', DB::raw('SUM(count) as average'))
            ->where('date', '<=', $date)
            ->where(fn($q) => whereDistrictPrefix($q, $region))
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
            ->where(fn($q) => whereDistrictPrefix($q, $region))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('score')
            ->toArray();
    }
}
