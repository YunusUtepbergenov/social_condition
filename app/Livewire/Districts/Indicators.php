<?php

namespace App\Livewire\Districts;

use App\Abstracts\DataType;
use App\Livewire\Concerns\HasMapVisualization;
use App\Models\{Merged, MergedOrg};
use App\Types\IndicatorType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB, Schema};
use Livewire\Component;

class Indicators extends Component
{
    use HasMapVisualization;

    public ?Collection $indicators = null;
    public ?string $activeIndicator = null;
    public ?array $columns = null;

    protected array $exclude = [
        'id', 'region_name', 'bs_scores_id', 'region_code', 'district_code', 'date',
        'bs_scores_bs_gen', 'bs_scores_b_s_q2', 'bs_scores_b_s_q4', 'bs_scores_b_s_q6',
        'bs_scores_bs_score_cur', 'bs_scores_b_s_q1', 'bs_scores_b_s_q3', 'bs_scores_b_s_q5',
        'bs_scores_bs_score_fut', 'bs_scores_month', 'score_bs_score_cur_predict', 'district_name',
        'ntl_data_cluster_ascending', 'ntl_data_cluster_avg', 'ntl_data_cluster_avg', 'ntl_data_cluster_max',
        'ntl_data_cluster_min', 'ntl_data_cluster_std', 'ntl_data_cluster_std', 'ntl_data_district', 'ntl_data_region',
        'ntl_data_region_avg', 'ntl_data_rep_avg', 'stratas_ishsizlar', 'customs_import', 'banks_deposits_balance_for_cur', 'stratas_ayollar_daftar', 'banks_not_paid_on_time_entrepreneurs',
        'students_students', 'students_attendance', 'stratas_nogiron_shaxslar', 'students_academic_leave', 'students_dropouts', 'sug_forest_abonents',
        'sug_forest_consump', 'sug_forest_paid', 'sug_forest_price', 'naturalgas_budget_ghu', 'naturalgas_budget_normativ', 'problems_davlat_boshqaruvida_strategik_rejalashtirish',
    ];

    protected $listeners = ['regionClicked', 'dateChanged', 'regionChanged', 'indicatorChanged'];

    public function mount(): void
    {
        $allColumns = Cache::remember("columns", 600 * 600, function () {
            return Schema::getColumnListing('merged_org');
        });
        $this->columns = array_values(array_diff($allColumns, $this->exclude));
        $this->activeIndicator = $this->columns[0] ?? null;
        $this->date = $this->getLatestDate();
        $this->dates = $this->getDates();
        $this->top_districts = $this->getDataClass()->getTopDistricts($this->activeRegion, $this->activeIndicator, $this->date);

        if (in_array($this->activeIndicator, $this->avg_indicators)) {
            $this->monthlyAvg = MergedOrg::select('date', DB::raw('AVG(' . $this->activeIndicator . ') as sum'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('sum')->toArray();
        } else {
            $this->monthlyAvg = MergedOrg::select('date', DB::raw('SUM(' . $this->activeIndicator . ') as sum'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('sum')->toArray();
        }

        $this->makeGeoJson();
        $this->dispatch('changeMonths', dates: $this->dates);
    }

    public function render(): View
    {
        return view('livewire.districts.indicators');
    }

    public function getTypeString(): string
    {
        return 'indicator';
    }

    public function getDataClass(): DataType
    {
        return new IndicatorType($this->activeIndicator);
    }

    public function getDates(): array
    {
        return MergedOrg::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->pluck('date')->toArray();
    }

    public function getLatestDate(): ?string
    {
        return MergedOrg::orderBy('date', 'DESC')->first()?->date;
    }

    public function getTumAvg(): array
    {
        $data = MergedOrg::select(DB::raw($this->activeIndicator . ' as score'), 'date')->where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray();
        $dates = array_fill_keys($this->dates, null);
        return array_values(array_merge($dates, $data));
    }

    public function getTumActualAvg(): array
    {
        return [];
    }

    public function indicatorChanged(string $indicator): void
    {
        if (in_array($indicator, $this->columns)) {
            $this->activeIndicator = $indicator;
            if ($this->active_tum) {
                $this->regionClicked($this->active_tum);
            } else {
                $this->dates = $this->getDates();
                if ($this->activeRegion != 'republic') {
                    $this->regionChanged($this->activeRegion);
                } else {
                    if (in_array($indicator, $this->avg_indicators)) {
                        $indicatorSum = MergedOrg::select('date', DB::raw('AVG(' . $this->activeIndicator . ') as sum'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('sum')->toArray();
                    } else {
                        $indicatorSum = MergedOrg::select('date', DB::raw('SUM(' . $this->activeIndicator . ') as sum'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('sum')->toArray();
                    }
                    $this->top_districts = MergedOrg::with('district')->select(['district_code', 'district_name', DB::raw($indicator . ' as score')])->where('date', $this->date)->orderByRaw('score DESC nulls last')->get();
                    $this->makeGeoJson();

                    $this->dispatch('updateChart', dates: $this->dates, data: $indicatorSum, actual: [], participants: [], type: $this->getTypeString());
                    $this->dispatch('updateMap', type: $this->getTypeString(), json: $this->json, top_districts: $this->top_districts, ranges: null);
                }
            }
        }
    }

    public function loadDateData(): void
    {
        // No special date data for indicators
    }

    public function regionClicked(string $tuman): void
    {
        $mergedDate = Merged::where('district_code', $tuman)->where('date', '<=', $this->date)->orderBy('date', 'DESC')->value('date');
        if (!$mergedDate) {
            return;
        }
        $record = Merged::select('demography_population as population')->where('date', $mergedDate)->where('district_code', $tuman)->first();
        if ($record?->population === null) {
            return;
        }
        $population = intval(Merged::select(DB::raw('SUM(demography_population) as population'))->where('date', $mergedDate)->groupBy('date')->first()->population);
        $tum_pop = intval($record->population);

        $class = $this->getDataClass();
        $this->top_districts = $class->getTopDistricts($this->activeRegion, $this->activeIndicator, $this->date);
        $this->active_tum = $tuman;

        $tum_avg = $this->getTumAvg();
        $actual_avg = $this->getTumActualAvg();

        $this->indicatorClass = '';
        $this->indicators = $class->getIndicators($tuman, $this->date, $population, $tum_pop, $this->avg_indicators);

        $this->dispatch('changeTable', tuman: $tuman, data: $tum_avg, actual: $actual_avg, participants: [], dates: $this->dates, date: $this->date, type: $this->getTypeString());
    }

    public function loadRegionClickedData(string $tuman): void
    {
        // Handled in regionClicked directly
    }

    protected function loadRepublicData(): void
    {
        $class = $this->getDataClass();
        $indicatorSum = $class->getRepublicData(in_array($this->activeIndicator, $this->avg_indicators));
        $this->dispatch('updateChart', dates: $this->dates, data: $indicatorSum, actual: [], participants: [], type: $this->getTypeString());
    }

    protected function updateRegionData(DataType $class, string $region): void
    {
        $firstParam = $class->getRegionPredicts($region, $this->date);
        $secondParam = $class->getRegionData($region, $this->date);
        $this->dispatch('updateChart', dates: $this->dates, data: $firstParam, actual: $secondParam, participants: [], type: $this->getTypeString());
    }
}
