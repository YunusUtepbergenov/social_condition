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

    public function showInfoModal($feature, $district, $data, $dataAvg , $date, $dates, $population, $tum_pop){
        $multiplier = 100000;

        $this->activeDistrict = $district;
        $regionCode = substr($district, 0, 4);

        $this->activeIndicator = $feature;
        $this->date = $date;
        $lastMonth = date("Y-m-d", strtotime($date . "-1 month"));
        $lastYear = date("Y-m-d", strtotime($date . "-12 month"));
        $nextMonth = date("Y-m-d", strtotime($date . "+1 month"));

        $this->lastYearDate = $lastYear;

        $startYear = $this->getFirstDateOfYearUsingDateTime($date);
        $startOfLastYear = $this->getFirstDateOfYearUsingDateTime($lastYear);

        $vil_pop = intval(Merged::select(DB::raw('SUM(demography_population) as population'))->where('date', $nextMonth)->where('district_code', 'LIKE', substr($district, 0, 4).'%')->groupBy('date')->first()->population);

        $this->curVal = end($data);


        $this->repAvg = MergedOrg::select(DB::raw('AVG('. $feature .') as score'))
                                ->where('date', '=', $date)
                                ->groupBY('date')
                                ->first();

        $this->vilAvg = MergedOrg::select(DB::raw('AVG('. $feature .') as score'))
                                ->where([
                                    ['date', '=', $date],
                                    ['district_code', 'LIKE', substr($district, 0, 4).'%'],
                                ])->groupBY('date')->first();

        $this->lastMonth = $data[$lastMonth];
        $this->lastYear = $data[$lastYear];

        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $this->curValNor = MergedOrg::select( DB::raw($feature .' / '. $tum_pop. '*'. $multiplier .' as score'))
                                ->where([
                                    ['date', '=', $date],
                                    ['district_code', '=', $district],
                                ])->first();

        $this->repAvgNor = MergedOrg::select(DB::raw('SUM('. $feature .')'. ' / '. $population. '*'. $multiplier .' as score'))
                                ->where('date', '=', $date)
                                ->groupBY('date')
                                ->first();

        $this->vilAvgNor = MergedOrg::select(DB::raw('SUM('. $feature .')'. ' / '. $vil_pop. '*'. $multiplier .' as score'))
                                ->where([
                                    ['date', '=', $date],
                                    ['district_code', 'LIKE', substr($district, 0, 4).'%'],
                                ])->groupBY('date')->first();

        $this->lastMonthNor = MergedOrg::select(DB::raw($feature .' / '. $tum_pop. '*'. $multiplier .' as score'))
                                    ->where([
                                        ['date', '=', $lastMonth],
                                        ['district_code', '=', $district],
                                    ])->first();

        $this->lastYearNor = MergedOrg::select(DB::raw($feature .' / '. $tum_pop. '*'. $multiplier .' as score'))
                                ->where([
                                    ['date', '=', $lastYear],
                                    ['district_code', '=', $district],
                                ])->first();

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $this->cumilativeLastYear = MergedOrg::select(DB::raw('SUM('. $feature .') as feature'))
                                    ->where('district_code', $district)
                                    ->whereBetween('date', [$startOfLastYear, $lastYear])
                                    ->first();

        $this->cumilativeLastYearNor = MergedOrg::select(DB::raw('SUM('. $feature .')' .' / '. $tum_pop. '*'. $multiplier. 'as feature'))
                                    ->where('district_code', $district)
                                    ->whereBetween('date', [$startOfLastYear, $lastYear])
                                    ->first();

        $this->cumilativeThisYear = MergedOrg::select(DB::raw('SUM('. $feature .') as feature'))
                                    ->where('district_code', $district)
                                    ->whereBetween('date', [$startYear, $date])
                                    ->first();
        $this->cumilativeThisYearNor = MergedOrg::select(DB::raw('SUM('. $feature .')' .' / '. $tum_pop. '*'. $multiplier .'as feature'))
                                    ->where('district_code', $district)
                                    ->whereBetween('date', [$startYear, $date])
                                    ->first();

        $this->ovrReg = MergedOrg::select(DB::raw('SUM('. $feature .') as feature'))
                                    ->where('district_code', 'Like', $regionCode.'%')
                                    ->where('date', $date)
                                    ->first();

        $this->ovrRep = MergedOrg::select(DB::raw('SUM('. $feature .') as feature'))
                                    ->where('date', $date)
                                    ->first();

        $this->dispatchBrowserEvent('openFormModal');
        $last_date = array_pop($dates);
        $this->emit('buildCharts', $data, $dataAvg, $dates);
    }
}
