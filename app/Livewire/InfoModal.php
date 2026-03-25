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

    public function toJSON(): array
    {
        return [];
    }

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

        $this->curVal = end($data);
        $this->lastMonth = $data[$lastMonth] ?? null;
        $this->lastYear = $data[$lastYear] ?? null;

        // Consolidated query 1: Get district's population history + scores for current/lastMonth/lastYear in one query
        $districtRows = MergedOrg::select('date', 'demography_population', DB::raw($feature . ' as score'))
            ->where('district_code', $district)
            ->whereIn('date', [$date, $lastMonth, $lastYear])
            ->get()
            ->keyBy('date');

        $tum_pop_arr = MergedOrg::select('demography_population as population', 'date')
            ->where('district_code', $district)
            ->orderBy('date', 'ASC')
            ->pluck('population', 'date')
            ->toArray();

        // Consolidated query 2: Republic-level aggregates (AVG + SUM) in one query
        $isAvg = in_array($feature, $avg_indicators);
        if ($isAvg) {
            $repAgg = MergedOrg::select(DB::raw("AVG({$feature}) as avg_score"))
                ->where('date', $date)->groupBy('date')->first();
            $vilAgg = MergedOrg::select(DB::raw("AVG({$feature}) as avg_score"))
                ->where('date', $date)->where(fn($q) => whereDistrictPrefix($q, $regionCode))
                ->groupBy('date')->first();

            $this->repAvg = $repAgg ? ['score' => $repAgg->avg_score] : null;
            $this->vilAvg = $vilAgg ? ['score' => $vilAgg->avg_score] : null;
            $this->repAvgNor = $repAgg ? ['score' => $repAgg->avg_score] : null;
            $this->vilAvgNor = $vilAgg ? ['score' => $vilAgg->avg_score] : null;
        } else {
            $repAgg = MergedOrg::select(DB::raw("AVG({$feature}) as avg_score, SUM({$feature}) as sum_score"))
                ->where('date', $date)->groupBy('date')->first();
            $vilAgg = MergedOrg::select(DB::raw("AVG({$feature}) as avg_score, SUM({$feature}) as sum_score"))
                ->where('date', $date)->where(fn($q) => whereDistrictPrefix($q, $regionCode))
                ->groupBy('date')->first();

            $this->repAvg = $repAgg ? ['score' => $repAgg->avg_score] : null;
            $this->vilAvg = $vilAgg ? ['score' => $vilAgg->avg_score] : null;
            $this->repAvgNor = ($population > 0 && $repAgg) ? ['score' => $repAgg->sum_score / $population * $multiplier] : null;

            $vil_pop_record = Merged::select(DB::raw('SUM(demography_population) as population'))
                ->where('date', $nextMonth)->where(fn($q) => whereDistrictPrefix($q, $regionCode))
                ->groupBy('date')->first();
            $vil_pop = $vil_pop_record ? intval($vil_pop_record->population) : 0;
            $this->vilAvgNor = ($vil_pop > 0 && $vilAgg) ? ['score' => $vilAgg->sum_score / $vil_pop * $multiplier] : null;
        }

        // Current value normalized
        $curRow = $districtRows->get($date);
        $this->curValNor = ($tum_pop > 0 && $curRow) ? $curRow->score / $tum_pop * $multiplier : null;

        // Last month normalized
        $lastMonthPop = $tum_pop_arr[$lastMonth] ?? 0;
        $lastMonthRow = $districtRows->get($lastMonth);
        $this->lastMonthNor = ($lastMonthPop > 0 && $lastMonthRow) ? $lastMonthRow->score / $lastMonthPop * $multiplier : null;

        // Last year normalized
        $lastYearPop = $tum_pop_arr[$lastYear] ?? 0;
        $lastYearRow = $districtRows->get($lastYear);
        $this->lastYearNor = ($lastYearPop > 0 && $lastYearRow) ? $lastYearRow->score / $lastYearPop * $multiplier : null;

        // Consolidated query 3: Cumulative sums (2 date ranges in one query using CASE)
        $cumulative = MergedOrg::select(
            DB::raw("SUM(CASE WHEN date BETWEEN '{$startOfLastYear}' AND '{$lastYear}' THEN {$feature} ELSE 0 END) as last_year_sum"),
            DB::raw("SUM(CASE WHEN date BETWEEN '{$startYear}' AND '{$date}' THEN {$feature} ELSE 0 END) as this_year_sum")
        )->where('district_code', $district)
            ->whereBetween('date', [$startOfLastYear, $date])
            ->first();

        $this->cumilativeLastYear = $cumulative ? ['feature' => $cumulative->last_year_sum] : null;
        $this->cumilativeThisYear = $cumulative ? ['feature' => $cumulative->this_year_sum] : null;
        $this->cumilativeLastYearNor = ($tum_pop > 0 && $cumulative) ? ['feature' => $cumulative->last_year_sum / $tum_pop * $multiplier] : null;
        $this->cumilativeThisYearNor = ($tum_pop > 0 && $cumulative) ? ['feature' => $cumulative->this_year_sum / $tum_pop * $multiplier] : null;

        // Regional and republic totals
        $ovrRegResult = MergedOrg::select(DB::raw("SUM({$feature}) as feature"))
            ->where(fn($q) => whereDistrictPrefix($q, $regionCode))
            ->where('date', $date)
            ->first();
        $this->ovrReg = $ovrRegResult ? ['feature' => $ovrRegResult->feature] : null;

        $ovrRepResult = MergedOrg::select(DB::raw("SUM({$feature}) as feature"))
            ->where('date', $date)
            ->first();
        $this->ovrRep = $ovrRepResult ? ['feature' => $ovrRepResult->feature] : null;

        $this->dispatch('openFormModal');
        $this->dispatch('buildCharts', data: array_values($data), dataAvg: $dataAvg, dates: $dates);
    }
}
