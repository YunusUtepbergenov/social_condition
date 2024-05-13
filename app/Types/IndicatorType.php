<?php

namespace App\Types;

use App\Abstracts\DataType;
use App\Models\Indicator_Ranking;
use App\Models\MergedOrg;
use Illuminate\Support\Facades\DB;

class IndicatorType extends DataType {
    public $activeIndicator;

    function __construct($indicator) {
        $this->activeIndicator = $indicator;
    }

    public function getTopDistricts($activeRegion, $activeIndicator, $date){
        if($activeRegion == 'republic')
                return MergedOrg::with('district')
                                ->select(['district_code', 'district_name', DB::raw($activeIndicator . ' as score')])
                                ->where('date', $date)
                                ->orderByRaw('score DESC nulls last')
                                ->get();
        else
            return MergedOrg::with('district')
                            ->select(['district_code', 'district_name', DB::raw($activeIndicator . ' as score')])
                            ->where('date', $date)
                            ->where('district_code', 'LIKE', $activeRegion.'%')
                            ->orderByRaw('score DESC nulls last')
                            ->get();
    }

    public function getRepublicData($bool){
        if($bool){
            return MergedOrg::select('date',  DB::raw('AVG('.$this->activeIndicator.') as sum'))
                            ->groupBy('date')
                            ->orderBy('date')
                            ->get()
                            ->pluck('sum')
                            ->toArray();
        }else{
            return MergedOrg::select('date',  DB::raw('SUM('.$this->activeIndicator.') as sum'))
                            ->groupBy('date')
                            ->orderBy('date')
                            ->get()
                            ->pluck('sum')
                            ->toArray();
        }
    }

    public function getIndicators($tuman, $date, $population, $tum_pop, $avg_indicators){
        $indicators = Indicator_Ranking::where('district_code', $tuman)->whereDate('date', $date)->orderBy('rank', 'DESC')->get();

        return $indicators->map(function($indicator) use ($tuman, $date,$population, $tum_pop, $avg_indicators){
            if(in_array($indicator->feature_name, $avg_indicators)){
                $indicator->average = (MergedOrg::select(DB::raw('AVG('. $indicator->feature_name. ') as avg'))->whereDate('date', $date)->groupBy('date')->first()->avg);
                $indicator->value = MergedOrg::select($indicator->feature_name. ' as indicator')->whereDate('date', $date)->where('district_code', $tuman)->first()->indicator;
            }else{
                $indicator->average = (MergedOrg::select(DB::raw('SUM('. $indicator->feature_name. ') as sum'))->where('date', $date)->groupBy('date')->first()->sum / $population) * 100000;
                $indicator->value = (MergedOrg::select($indicator->feature_name. ' as indicator')->where('date', $date)->where('district_code', $tuman)->first()->indicator / $tum_pop) * 100000;
            }
            return $indicator;
        });
    }

    public function getRegionData($region, $date){
        return [];
    }

    public function getRegionParticipants($region, $date){
        return [];
    }

    public function getRegionPredicts($region, $date){
        return MergedOrg::select('date',  DB::raw('SUM('.$this->activeIndicator.') as sum'))
                        ->where('district_code', 'LIKE', $region.'%')
                        ->where('date', '<=', $date)
                        ->groupBy('date')->orderBy('date')
                        ->get()->pluck('sum')
                        ->toArray();
    }
}
