<?php

namespace App\Http\Livewire;

use App\Models\Merged;
use App\Models\MergedOrg;
use DateTime;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class InfoModal extends Component
{
    protected $listeners = ['showInfoModal'];

    public $activeIndicator, $activeDistrict, $date, $lastYear;
    public $repAvg, $vilAvg, $curVal, $lastMonth, $lastYearDate;
    public $cumilativeThisYear, $cumilativeLastYear, $cumilativeLastYearNor, $cumilativeThisYearNor;
    public $ovrReg, $ovrRep;
    public $repAvgNor, $vilAvgNor, $curValNor, $lastMonthNor, $lastYearNor;

    public function render()
    {
        return view('livewire.info-modal');
    }

    public function getFirstDateOfYearUsingDateTime($date) {
        $date = new DateTime($date);
        $date->setDate($date->format('Y'), 1, 1);
        return $date->format('Y-m-d');
    }

    public function showInfoModal($feature, $district, $data, $dataAvg ,$date, $dates, $population, $tum_pop){
        $multiplier = 100000;

        $this->activeDistrict = $district;
        $regionCode = substr($district, 0, 4);

        $this->activeIndicator = $feature;
        $this->date = $date;
        $lastMonth = date("Y-m-d", strtotime($date . "-1 month"));
        $lastYear = date("Y-m-d", strtotime($date . "-12 month"));
        $this->lastYearDate = $lastYear;

        $startYear = $this->getFirstDateOfYearUsingDateTime($date);
        $startOfLastYear = $this->getFirstDateOfYearUsingDateTime($lastYear);

        $vil_pop = intval(Merged::select(DB::raw('SUM(demography_population) as population'))->where('date', $date)->where('district_code', 'LIKE', substr($district, 0, 4).'%')->groupBy('date')->first()->population);

        $this->curVal = Merged::select($feature)
                                ->where([
                                    ['date', '=', $date],
                                    ['district_code', '=', $district],   
                                ])->first();

        $this->repAvg = Merged::select(DB::raw('AVG('. $feature .') as score'))
                                ->where('date', '=', $date)
                                ->groupBY('date')
                                ->first();

        $this->vilAvg = Merged::select(DB::raw('AVG('. $feature .') as score'))
                                ->where([
                                    ['date', '=', $date],
                                    ['district_code', 'LIKE', substr($district, 0, 4).'%'],   
                                ])->groupBY('date')->first();

        $this->lastMonth = Merged::select($feature)
                                    ->where([
                                        ['date', '=', $lastMonth],
                                        ['district_code', '=', $district],   
                                    ])->first();
        
        $this->lastYear = Merged::select($feature)
                                ->where([
                                    ['date', '=', $lastYear],
                                    ['district_code', '=', $district],   
                                ])->first();

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $this->curValNor = Merged::select( DB::raw($feature .' / '. $tum_pop. '*'. $multiplier .' as score'))
                                ->where([
                                    ['date', '=', $date],
                                    ['district_code', '=', $district],   
                                ])->first();

        $this->repAvgNor = Merged::select(DB::raw('SUM('. $feature .')'. ' / '. $population. '*'. $multiplier .' as score'))
                                ->where('date', '=', $date)
                                ->groupBY('date')
                                ->first();

        $this->vilAvgNor = Merged::select(DB::raw('SUM('. $feature .')'. ' / '. $vil_pop. '*'. $multiplier .' as score'))
                                ->where([
                                    ['date', '=', $date],
                                    ['district_code', 'LIKE', substr($district, 0, 4).'%'],   
                                ])->groupBY('date')->first();

        $this->lastMonthNor = Merged::select(DB::raw($feature .' / '. $tum_pop. '*'. $multiplier .' as score'))
                                    ->where([
                                        ['date', '=', $lastMonth],
                                        ['district_code', '=', $district],   
                                    ])->first();

        $this->lastYearNor = Merged::select(DB::raw($feature .' / '. $tum_pop. '*'. $multiplier .' as score'))
                                ->where([
                                    ['date', '=', $lastYear],
                                    ['district_code', '=', $district],   
                                ])->first();

        ///////////////////////////////////////////////////////////////////////////

        $this->cumilativeLastYear = Merged::select(DB::raw('SUM('. $feature .') as feature'))
                                    ->where('district_code', $district)
                                    ->whereBetween('date', [$startOfLastYear, $lastYear])
                                    ->first();

        $this->cumilativeLastYearNor = Merged::select(DB::raw('SUM('. $feature .')' .' / '. $tum_pop. '*'. $multiplier. 'as feature'))
                                    ->where('district_code', $district)
                                    ->whereBetween('date', [$startOfLastYear, $lastYear])
                                    ->first();


        $this->cumilativeThisYear = Merged::select(DB::raw('SUM('. $feature .') as feature'))
                                    ->where('district_code', $district)
                                    ->whereBetween('date', [$startYear, $date])
                                    ->first();
        $this->cumilativeThisYearNor = Merged::select(DB::raw('SUM('. $feature .')' .' / '. $tum_pop. '*'. $multiplier .'as feature'))
                                    ->where('district_code', $district)
                                    ->whereBetween('date', [$startYear, $date])
                                    ->first();

        $this->ovrReg = Merged::select(DB::raw('SUM('. $feature .') as feature'))
                                    ->where('district_code', 'Like', $regionCode.'%')
                                    ->where('date', $date)
                                    ->first();

        $this->ovrRep = Merged::select(DB::raw('SUM('. $feature .') as feature'))
                                    ->where('date', $date)
                                    ->first();

        $this->dispatchBrowserEvent('openFormModal');
        $this->emit('buildCharts', $data, $dataAvg, $dates);
    }
}
