<?php

namespace App\Types;

use App\Abstracts\DataType;
use App\Models\{BsScore, BsScorePrediction, Merged, Mood_Ranking, MutualInfo, Range, SurveyRange};
use Illuminate\Support\Collection;

class MoodType extends DataType
{
    public static function getRanges(string $date): Collection
    {
        $actualDates = BsScore::select('date')->distinct('date')->get()->pluck('date')->toArray();
        $predictionDates = BsScorePrediction::select('date')->distinct('date')->get()->pluck('date')->toArray();

        $predictionDates = array_diff($predictionDates, $actualDates);

        if (in_array($date, $predictionDates)) {
            return Range::where('date', $date)->get();
        }

        return SurveyRange::where('date', $date)->get();
    }

    public function getTopDistricts(string $activeRegion, ?string $activeIndicator, string $date): Collection
    {
        $actualDates = BsScore::select('date')->distinct('date')->get()->pluck('date')->toArray();
        $predictionDates = BsScorePrediction::select('date')->distinct('date')->get()->pluck('date')->toArray();

        $predictionDates = array_diff($predictionDates, $actualDates);

        if ($activeRegion == 'republic') {
            if (!in_array($date, $predictionDates)) {
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

        if (in_array($date, $predictionDates)) {
            return BsScorePrediction::with('district')
                ->where('date', $date)
                ->where('district_code', 'LIKE', $activeRegion . '%')
                ->orderByRaw('score DESC nulls last')
                ->get();
        }

        return BsScore::with('district')->selectRaw('district_code, label,date, bs_score_cur as score')
            ->where('date', $date)
            ->where('district_code', 'LIKE', $activeRegion . '%')
            ->orderByRaw('score DESC nulls last')
            ->get();
    }

    public function getLabel(string $date, string $district): mixed
    {
        $actualDates = BsScore::select('date')->distinct('date')->get()->pluck('date')->toArray();
        $predictionDates = BsScorePrediction::select('date')->distinct('date')->get()->pluck('date')->toArray();

        $predictionDates = array_diff($predictionDates, $actualDates);

        if (in_array($date, $predictionDates)) {
            return BsScorePrediction::where('district_code', $district)->where('date', $date)->first()->label;
        }

        return BsScore::where('district_code', $district)->where('date', $date)->first()->label;
    }

    public function getIndicators(string $tuman, string $date, int $population, int $tum_pop, array $avg_indicators): Collection
    {
        $actualDates = BsScore::select('date')->distinct('date')->get()->pluck('date')->toArray();
        $predictionDates = BsScorePrediction::select('date')->distinct('date')->get()->pluck('date')->toArray();

        $predictionDates = array_diff($predictionDates, $actualDates);

        if (in_array($date, $predictionDates)) {
            $indicators = MutualInfo::where('district_code', $tuman)->whereDate('date', $date)->orderBy('mutual_info', 'DESC')->get();
        } else {
            $indicators = Mood_Ranking::where('district_code', $tuman)->whereDate('date', $date)->orderBy('mutual_info', 'DESC')->get();
        }

        return $indicators->map(function ($indicator) use ($tuman, $date, $population, $tum_pop, $avg_indicators) {
            if (in_array($indicator->feature_name, $avg_indicators)) {
                $indicator->average = (Merged::selectRaw('AVG(' . $indicator->feature_name . ') as avg')->whereDate('date', $date)->groupBy('date')->first()->avg);
                $indicator->value = Merged::select($indicator->feature_name . ' as indicator')->whereDate('date', $date)->where('district_code', $tuman)->first()->indicator;
            } else {
                $indicator->average = (Merged::selectRaw('SUM(' . $indicator->feature_name . ') as sum')->where('date', $date)->groupBy('date')->first()->sum / $population) * 100000;
                $indicator->value = (Merged::select($indicator->feature_name . ' as indicator')->where('date', $date)->where('district_code', $tuman)->first()->indicator / $tum_pop) * 100000;
            }
            return $indicator;
        });
    }

    public function getRegionPredicts(string $region, string $date): array
    {
        return BsScorePrediction::selectRaw('date, AVG(score) as average')
            ->where('district_code', 'LIKE', $region . '%')
            ->where('date', '<=', $date)
            ->groupBy('date')->orderBy('date')
            ->get()->pluck('average')
            ->toArray();
    }

    public function getRegionData(string $region, string $date): array
    {
        return BsScore::selectRaw('date, AVG(bs_score_cur) as average')
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
        return [];
    }
}
