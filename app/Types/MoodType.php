<?php

namespace App\Types;

use App\Abstracts\DataType;
use App\Models\BsScore;
use App\Models\BsScorePrediction;
use App\Models\Merged;
use App\Models\MutualInfo;
use Illuminate\Support\Facades\DB;

class MoodType extends DataType{

    public function getTopDistricts($activeRegion, $activeIndicator, $date){
        if($activeRegion == 'republic')
            return BsScorePrediction::with('district')
                    ->where('date', $date)
                    ->orderBy('score', 'DESC')
                    ->get();
        else
            return BsScorePrediction::with('district')
                    ->where('date', $date)
                    ->where('district_code', 'LIKE', $activeRegion.'%')
                    ->orderByRaw('score DESC nulls last')
                    ->get();
    }

    public function getIndicators($tuman, $date, $population, $tum_pop, $avg_indicators){
        $indicators = MutualInfo::where('district_code', $tuman)->whereDate('date', $date)->orderBy('mutual_info', 'DESC')->get();
        return $indicators->map(function($indicator) use ($tuman, $date,$population, $tum_pop, $avg_indicators){
            if(in_array($indicator->feature_name, $avg_indicators)){
                $indicator->average = (Merged::select(DB::raw('AVG('. $indicator->feature_name. ') as avg'))->whereDate('date', $date)->groupBy('date')->first()->avg);
                $indicator->value = Merged::select($indicator->feature_name. ' as indicator')->whereDate('date', $date)->where('district_code', $tuman)->first()->indicator;
            }else{
                $indicator->average = (Merged::select(DB::raw('SUM('. $indicator->feature_name. ') as sum'))->where('date', $date)->groupBy('date')->first()->sum / $population) * 100000;
                $indicator->value = (Merged::select($indicator->feature_name. ' as indicator')->where('date', $date)->where('district_code', $tuman)->first()->indicator / $tum_pop) * 100000;
            }
            return $indicator;
        });
    }

    public function getRegionPredicts($region, $date){
        return BsScorePrediction::select('date',  DB::raw('AVG(score) as average'))
                ->where('district_code', 'LIKE', $region.'%')
                ->where('date', '<=', $date)
                ->groupBy('date')->orderBy('date')
                ->get()->pluck('average')
                ->toArray();
    }

    public function getRegionData($region, $date){
        return BsScore::select('date', DB::raw('AVG(bs_score_cur) as average'))
                        ->where('date', '<=', $date)
                        ->where('district_code', 'LIKE', $region.'%')
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get()
                        ->pluck('average')
                        ->toArray();
    }

    public function getRegionParticipants($region, $date){
        return [];
    }
}
