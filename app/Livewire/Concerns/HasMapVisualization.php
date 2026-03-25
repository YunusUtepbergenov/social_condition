<?php

namespace App\Livewire\Concerns;

use App\Abstracts\DataType;
use App\Models\{Merged, MergedOrg};
use Illuminate\Support\Facades\{Cache, DB};
use Livewire\Attributes\Renderless;

trait HasMapVisualization
{
    public ?string $active_tum = null;
    public ?string $activeRegion = 'republic';
    public ?string $date = null;
    public array $dates = [];
    public mixed $top_districts = null;
    public array $monthlyAvg = [];
    public array $actualAvg = [];
    public string $indicatorClass = 'highlightRed';

    public array $avg_indicators = [
        "weather_temperature", "weather_precipitation", "weather_pollution",
        "weather_wind", "weather_pressure", "weather_humidity", "electr_population_price",
        "electr_pop_nogas_price", "electr_other_price", "electr_budget_price", "electr_public_utilities_price",
        "electr_industry_price", "electr_сommercial_price", "electr_agriculture_price", "electr_transport_construction_price",
        "sug_population_price", "sug_mtm_price", "sug_military_price", "sug_forest_price", "ntl_data_ntl_mean",
        "liquified_gases_avg_price", "liquified_gases_overall_price", "naturalgas_agtksh_price", "naturalgas_budget_price",
        "naturalgas_heat_price", "naturalgas_population_price", "naturalgas_sanoat_price", "naturalgas_sme_price",
        "problems_narx_navo_narx_sohasida_davlat_siyosati", "product_prices_price",
    ];

    public function toJSON(): array
    {
        return [];
    }

    abstract public function getTypeString(): string;

    abstract public function getDataClass(): DataType;

    abstract public function getDates(): array;

    abstract public function getLatestDate(): ?string;

    abstract public function getTumAvg(): array;

    abstract public function getTumActualAvg(): array;

    abstract public function loadDateData(): void;

    abstract public function loadRegionClickedData(string $tuman): void;

    public function getScoreOverlay(): array
    {
        return [
            'scores' => $this->top_districts->pluck('score', 'district_code')->toArray(),
            'labels' => $this->top_districts->pluck('label', 'district_code')->toArray(),
        ];
    }

    public function regionChanged(string $region): void
    {
        $this->dispatch('regionSelected', region: $region);
        $this->activeRegion = $region;
        $this->active_tum = null;

        $class = $this->getDataClass();
        $this->top_districts = $class->getTopDistricts($this->activeRegion, $this->activeIndicator ?? null, $this->date);

        if ($region !== 'republic') {
            $this->updateRegionData($class, $region);
        } else {
            $this->dateChanged($this->date);
        }

        $this->dispatch('updateMap', type: $this->getTypeString(), overlay: $this->getScoreOverlay(), top_districts: $this->top_districts, ranges: $this->ranges ?? null);
    }

    #[Renderless]
    public function openModal(string $feature): void
    {
        validateColumn($feature, 'merged_org');

        $mergedDate = Merged::where('district_code', $this->active_tum)->where('date', '<=', $this->date)->orderBy('date', 'DESC')->value('date') ?? $this->date;
        $tum_pop = intval(Merged::select('demography_population as population')->where('date', $mergedDate)->where('district_code', $this->active_tum)->first()?->population ?? 0);
        $population = intval(Merged::select(DB::raw('SUM(demography_population) as population'))->where('date', $mergedDate)->groupBy('date')->first()?->population ?? 0);

        if ($this->getTypeString() != 'indicator') {
            $date = date("Y-m-d", strtotime($this->date . "-2 month"));
        } else {
            $date = $this->date;
        }

        $dates = MergedOrg::select('date')->distinct()->where('date', '<=', $date)->orderBy('date', 'ASC')->pluck('date')->toArray();
        $data = MergedOrg::select(DB::raw($feature . ' as score'), 'date')->where('district_code', $this->active_tum)->whereIn('date', $dates)->orderBy('date', 'ASC')->get()->pluck('score', 'date')->toArray();
        $dataAvg = MergedOrg::select(DB::raw('CASE WHEN demography_population > 0 THEN ' . $feature . ' * 100000 / demography_population ELSE NULL END as score'), 'date')->where('district_code', $this->active_tum)->whereIn('date', $dates)->orderBy('date', 'ASC')->get()->pluck('score')->toArray();
        $this->dispatch('showInfoModal', feature: $feature, district: $this->active_tum, data: $data, dataAvg: $dataAvg, date: $date, dates: $dates, population: $population, tum_pop: $tum_pop, avg_indicators: $this->avg_indicators);
    }

    public function dateChanged(string $date): void
    {
        $this->date = $date;
        $this->dates = $this->getDates();

        $this->loadDateData();

        $class = $this->getDataClass();

        if ($this->active_tum) {
            $this->regionClicked($this->active_tum);
            $this->dispatch('updateMap', type: $this->getTypeString(), overlay: $this->getScoreOverlay(), top_districts: $this->top_districts, ranges: $this->ranges ?? null);
        } else {
            if ($this->activeRegion != 'republic') {
                $this->regionChanged($this->activeRegion);
            } else {
                $this->top_districts = $class->getTopDistricts($this->activeRegion, $this->activeIndicator ?? null, $this->date);
                $this->loadRepublicData();
                $this->dispatch('updateMap', type: $this->getTypeString(), overlay: $this->getScoreOverlay(), top_districts: $this->top_districts, ranges: $this->ranges ?? null);
            }
        }
    }

    abstract protected function loadRepublicData(): void;

    abstract protected function updateRegionData(DataType $class, string $region): void;
}
