<?php

namespace App\Livewire;

use App\Models\Merged;
use App\Models\MergedOrg;
use DateTime;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class InfoModal extends Component
{
    protected $listeners = ['showInfoModal'];

    public ?string $activeIndicator = null;
    public ?string $activeDistrict = null;
    public ?string $date = null;
    public mixed $lastYear = null;
    public mixed $repAvg = null;
    public mixed $vilAvg = null;
    public mixed $curVal = null;
    public mixed $lastMonth = null;
    public ?string $lastYearDate = null;
    public mixed $cumilativeThisYear = null;
    public mixed $cumilativeLastYear = null;
    public mixed $cumilativeLastYearNor = null;
    public mixed $cumilativeThisYearNor = null;
    public mixed $ovrReg = null;
    public mixed $ovrRep = null;
    public mixed $repAvgNor = null;
    public mixed $vilAvgNor = null;
    public float|int|null $curValNor = null;
    public float|int|null $lastMonthNor = null;
    public float|int|null $lastYearNor = null;

    public function render(): View
    {
        return view('livewire.info-modal');
    }

    public function getFirstDateOfYearUsingDateTime(string $date): string
    {
        $date = new DateTime($date);
        $date->setDate((int) $date->format('Y'), 1, 1);
        return $date->format('Y-m-d');
    }

    public function showInfoModal(string $feature, string $district, array $data, array $dataAvg, string $date, array $dates, int $population, int $tum_pop, array $avg_indicators = []): void
    {
        $tum_pop_arr = MergedOrg::select('demography_population as population', 'date')->where('district_code', $district)->orderBy('date', 'ASC')->get()->pluck('population', 'date')->toArray();
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

        $vil_pop = intval(Merged::select(DB::raw('SUM(demography_population) as population'))->where('date', $nextMonth)->where('district_code', 'LIKE', substr($district, 0, 4) . '%')->groupBy('date')->first()->population);

        $this->curVal = end($data);

        $this->repAvg = MergedOrg::select(DB::raw('AVG(' . $feature . ') as score'))
            ->where('date', '=', $date)
            ->groupBY('date')
            ->first();

        $this->vilAvg = MergedOrg::select(DB::raw('AVG(' . $feature . ') as score'))
            ->where([
                ['date', '=', $date],
                ['district_code', 'LIKE', substr($district, 0, 4) . '%'],
            ])->groupBY('date')->first();

        $this->lastMonth = $data[$lastMonth];
        $this->lastYear = $data[$lastYear];

        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $this->curValNor = MergedOrg::select(DB::raw($feature . ' as score'))
            ->where([
                ['date', '=', $date],
                ['district_code', '=', $district],
            ])->first()->score / $tum_pop * $multiplier;

        if (in_array($feature, $avg_indicators)) {
            $this->repAvgNor = MergedOrg::select(DB::raw('AVG(' . $feature . ') as score'))->whereDate('date', $date)->groupBy('date')->first();
            $this->vilAvgNor = MergedOrg::select(DB::raw('AVG(' . $feature . ') as score'))->whereDate('date', $date)->where('district_code', 'LIKE', substr($district, 0, 4) . '%')->first();
        } else {
            $this->repAvgNor = MergedOrg::select(DB::raw('SUM(' . $feature . ')' . ' / ' . $population . '*' . $multiplier . ' as score'))
                ->where('date', '=', $date)
                ->groupBY('date')
                ->first();

            $this->vilAvgNor = MergedOrg::select(DB::raw('SUM(' . $feature . ')' . ' / ' . $vil_pop . '*' . $multiplier . ' as score'))
                ->where([
                    ['date', '=', $date],
                    ['district_code', 'LIKE', substr($district, 0, 4) . '%'],
                ])->groupBY('date')->first();
        }

        $this->lastMonthNor = MergedOrg::select(DB::raw($feature . ' as score'))
            ->where([
                ['date', '=', $lastMonth],
                ['district_code', '=', $district],
            ])->first()->score / $tum_pop_arr[$lastMonth] * $multiplier;

        $this->lastYearNor = MergedOrg::select(DB::raw($feature . ' as score'))
            ->where([
                ['date', '=', $lastYear],
                ['district_code', '=', $district],
            ])->first()->score / $tum_pop_arr[$lastYear] * $multiplier;

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $this->cumilativeLastYear = MergedOrg::select(DB::raw('SUM(' . $feature . ') as feature'))
            ->where('district_code', $district)
            ->whereBetween('date', [$startOfLastYear, $lastYear])
            ->first();

        $this->cumilativeLastYearNor = MergedOrg::select(DB::raw('SUM(' . $feature . ')' . ' / ' . $tum_pop . '*' . $multiplier . 'as feature'))
            ->where('district_code', $district)
            ->whereBetween('date', [$startOfLastYear, $lastYear])
            ->first();

        $this->cumilativeThisYear = MergedOrg::select(DB::raw('SUM(' . $feature . ') as feature'))
            ->where('district_code', $district)
            ->whereBetween('date', [$startYear, $date])
            ->first();
        $this->cumilativeThisYearNor = MergedOrg::select(DB::raw('SUM(' . $feature . ')' . ' / ' . $tum_pop . '*' . $multiplier . 'as feature'))
            ->where('district_code', $district)
            ->whereBetween('date', [$startYear, $date])
            ->first();

        $this->ovrReg = MergedOrg::select(DB::raw('SUM(' . $feature . ') as feature'))
            ->where('district_code', 'Like', $regionCode . '%')
            ->where('date', $date)
            ->first();

        $this->ovrRep = MergedOrg::select(DB::raw('SUM(' . $feature . ') as feature'))
            ->where('date', $date)
            ->first();

        $this->dispatch('openFormModal');
        $this->dispatch('buildCharts', data: $data, dataAvg: $dataAvg, dates: $dates);
    }
}
