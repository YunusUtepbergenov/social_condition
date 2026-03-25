<?php

namespace App\Types;

use App\Abstracts\DataType;
use App\Models\{BsScore, BsScorePrediction, Merged, Mood_Ranking, MutualInfo, Range, SurveyRange};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MoodType extends DataType
{
    protected static function isPredictionDate(string $date): bool
    {
        return Cache::remember("mood_is_prediction_{$date}", 3600, function () use ($date) {
            $actualDates = BsScore::select('date')->distinct('date')->pluck('date')->toArray();
            return !in_array($date, $actualDates) && BsScorePrediction::where('date', $date)->exists();
        });
    }

    public static function getRanges(string $date): Collection
    {
        if (static::isPredictionDate($date)) {
            return Range::where('date', $date)->get();
        }

        return SurveyRange::where('date', $date)->get();
    }

    public function getTopDistricts(string $activeRegion, ?string $activeIndicator, string $date): Collection
    {
        $isPrediction = static::isPredictionDate($date);

        if ($activeRegion == 'republic') {
            if (!$isPrediction) {
                return BsScore::with('district')->selectRaw('district_code, label, date, bs_score_cur as score')
                    ->where('date', $date)
                    ->orderBy('score', 'DESC')
                    ->get();
            }

            return BsScorePrediction::with('district')
                ->where('date', $date)
                ->orderBy('score', 'DESC')
                ->get();
        }

        if ($isPrediction) {
            return BsScorePrediction::with('district')
                ->where('date', $date)
                ->where(fn($q) => whereDistrictPrefix($q, $activeRegion))
                ->orderByRaw('score DESC nulls last')
                ->get();
        }

        return BsScore::with('district')->selectRaw('district_code, label,date, bs_score_cur as score')
            ->where('date', $date)
            ->where(fn($q) => whereDistrictPrefix($q, $activeRegion))
            ->orderByRaw('score DESC nulls last')
            ->get();
    }

    public function getLabel(string $date, string $district): mixed
    {
        if (static::isPredictionDate($date)) {
            return BsScorePrediction::where('district_code', $district)->where('date', $date)->first()->label;
        }

        return BsScore::where('district_code', $district)->where('date', $date)->first()->label;
    }

    public function getIndicators(string $tuman, string $date, int $population, int $tum_pop, array $avg_indicators): Collection
    {
        if (static::isPredictionDate($date)) {
            $indicators = MutualInfo::where('district_code', $tuman)->whereDate('date', $date)->orderBy('mutual_info', 'DESC')->get();
        } else {
            $indicators = Mood_Ranking::where('district_code', $tuman)->whereDate('date', $date)->orderBy('mutual_info', 'DESC')->get();
        }

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
        return BsScorePrediction::selectRaw('date, AVG(score) as average')
            ->where(fn($q) => whereDistrictPrefix($q, $region))
            ->where('date', '<=', $date)
            ->groupBy('date')->orderBy('date')
            ->get()->pluck('average')
            ->toArray();
    }

    public function getRegionData(string $region, string $date): array
    {
        return BsScore::selectRaw('date, AVG(bs_score_cur) as average')
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
        return [];
    }
}
