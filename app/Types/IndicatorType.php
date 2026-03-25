<?php

namespace App\Types;

use App\Abstracts\DataType;
use App\Models\Indicator_Ranking;
use App\Models\MergedOrg;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IndicatorType extends DataType
{
    public function __construct(public ?string $activeIndicator = null)
    {
        if ($activeIndicator) {
            validateColumn($activeIndicator, 'merged_org');
        }
    }

    public function getTopDistricts(string $activeRegion, ?string $activeIndicator, string $date): Collection
    {
        if ($activeIndicator) {
            validateColumn($activeIndicator, 'merged_org');
        }

        if ($activeRegion == 'republic') {
            return MergedOrg::with('district')
                ->select(['district_code', 'district_name', DB::raw($activeIndicator . ' as score')])
                ->where('date', $date)
                ->orderByRaw('score DESC nulls last')
                ->get();
        }

        return MergedOrg::with('district')
            ->select(['district_code', 'district_name', DB::raw($activeIndicator . ' as score')])
            ->where('date', $date)
            ->where(fn($q) => whereDistrictPrefix($q, $activeRegion))
            ->orderByRaw('score DESC nulls last')
            ->get();
    }

    public function getRepublicData(bool $bool): array
    {
        if ($bool) {
            return MergedOrg::select('date', DB::raw('AVG(' . $this->activeIndicator . ') as sum'))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('sum')
                ->toArray();
        }

        return MergedOrg::select('date', DB::raw('SUM(' . $this->activeIndicator . ') as sum'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('sum')
            ->toArray();
    }

    public function getIndicators(string $tuman, string $date, int $population, int $tum_pop, array $avg_indicators): Collection
    {
        $indicators = Indicator_Ranking::where('district_code', $tuman)->whereDate('date', $date)->orderBy('rank', 'DESC')->get();

        if ($indicators->isEmpty()) {
            return $indicators;
        }

        $featureNames = $indicators->pluck('feature_name')->toArray();
        validateColumns($featureNames, 'merged_org');
        $districtRow = MergedOrg::where('date', $date)->where('district_code', $tuman)->first();

        $avgColumns = array_intersect($featureNames, $avg_indicators);
        $sumColumns = array_diff($featureNames, $avg_indicators);

        $avgValues = [];
        if (!empty($avgColumns)) {
            $selects = array_map(fn($col) => "AVG({$col}) as {$col}", $avgColumns);
            $avgValues = (array) MergedOrg::selectRaw(implode(', ', $selects))->where('date', $date)->groupBy('date')->first()?->getAttributes();
        }

        $sumValues = [];
        if (!empty($sumColumns)) {
            $selects = array_map(fn($col) => "SUM({$col}) as {$col}", $sumColumns);
            $sumValues = (array) MergedOrg::selectRaw(implode(', ', $selects))->where('date', $date)->groupBy('date')->first()?->getAttributes();
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

    public function getRegionData(string $region, string $date): array
    {
        return [];
    }

    public function getRegionParticipants(string $region, string $date): array
    {
        return [];
    }

    public function getRegionPredicts(string $region, string $date): array
    {
        return MergedOrg::select('date', DB::raw('SUM(' . $this->activeIndicator . ') as sum'))
            ->where(fn($q) => whereDistrictPrefix($q, $region))
            ->where('date', '<=', $date)
            ->groupBy('date')->orderBy('date')
            ->get()->pluck('sum')
            ->toArray();
    }
}
