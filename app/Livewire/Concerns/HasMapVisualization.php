<?php

namespace App\Livewire\Concerns;

use App\Abstracts\DataType;
use App\Models\{Merged, MergedOrg};
use Illuminate\Support\Facades\{Cache, DB};

trait HasMapVisualization
{
    public ?string $active_tum = null;
    public ?string $activeRegion = 'republic';
    public ?array $json = null;
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

    abstract public function getTypeString(): string;

    abstract public function getDataClass(): DataType;

    abstract public function getDates(): array;

    abstract public function getLatestDate(): ?string;

    abstract public function getTumAvg(): array;

    abstract public function getTumActualAvg(): array;

    abstract public function loadDateData(): void;

    abstract public function loadRegionClickedData(string $tuman): void;

    public function makeGeoJson(): void
    {
        $path = public_path('geojson/clean.json');
        $this->json = Cache::remember('geojson_districts', 60 * 60 * 24, function () use ($path) {
            return json_decode(file_get_contents($path), true);
        });

        $districtScores = $this->top_districts->pluck('score', 'district_code')->toArray();
        $districtLabels = $this->top_districts->pluck('label', 'district_code')->toArray();

        foreach ($this->json['features'] as &$feature) {
            $districtCode = $feature['properties']['district_code'];
            if (isset($districtScores[$districtCode])) {
                $feature['factors']['score'] = $districtScores[$districtCode];
                if (isset($districtLabels[$districtCode])) {
                    $feature['factors']['label'] = $districtLabels[$districtCode];
                }
            }
        }
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

        $this->makeGeoJson();
        $this->dispatch('updateMap', type: $this->getTypeString(), json: $this->json, top_districts: $this->top_districts, ranges: $this->ranges ?? null);
    }

    public function openModal(string $feature): void
    {
        $columns = Cache::remember("columns", 600 * 600, function () {
            return \Illuminate\Support\Facades\Schema::getColumnListing('merged_org');
        });

        if (in_array($feature, $columns)) {
            $mergedDate = Merged::where('district_code', $this->active_tum)->where('date', '<=', $this->date)->orderBy('date', 'DESC')->value('date') ?? $this->date;
            $tum_pop = intval(Merged::select('demography_population as population')->where('date', $mergedDate)->where('district_code', $this->active_tum)->first()->population);
            $population = intval(Merged::select(DB::raw('SUM(demography_population) as population'))->where('date', $mergedDate)->groupBy('date')->first()->population);

            if ($this->getTypeString() != 'indicator') {
                $date = date("Y-m-d", strtotime($this->date . "-2 month"));
            } else {
                $date = $this->date;
            }

            $data = MergedOrg::select(DB::raw($feature . ' as score'), 'date')->where('district_code', $this->active_tum)->where('date', '<=', $date)->orderBy('date', 'ASC')->get()->pluck('score', 'date')->toArray();
            $dataAvg = MergedOrg::select(DB::raw($feature . '* 100000 / demography_population as score'), 'date')->where('district_code', $this->active_tum)->where('date', '<=', $date)->orderBy('date', 'ASC')->get()->pluck('score')->toArray();
            $dates = MergedOrg::select('date')->distinct()->where('date', '<', $date)->orderBy('date', 'ASC')->pluck('date')->toArray();
            $this->dispatch('showInfoModal', feature: $feature, district: $this->active_tum, data: $data, dataAvg: $dataAvg, date: $date, dates: $dates, population: $population, tum_pop: $tum_pop, avg_indicators: $this->avg_indicators);
            $this->regionClicked($this->active_tum);
        }
    }

    public function dateChanged(string $date): void
    {
        $this->date = $date;
        $this->dates = $this->getDates();

        $this->loadDateData();

        $class = $this->getDataClass();

        if ($this->active_tum) {
            $this->regionClicked($this->active_tum);
            $this->makeGeoJson();
            $this->dispatch('updateMap', type: $this->getTypeString(), json: $this->json, top_districts: $this->top_districts, ranges: $this->ranges ?? null);
        } else {
            if ($this->activeRegion != 'republic') {
                $this->regionChanged($this->activeRegion);
            } else {
                $this->top_districts = $class->getTopDistricts($this->activeRegion, $this->activeIndicator ?? null, $this->date);
                $this->loadRepublicData();
                $this->makeGeoJson();
                $this->dispatch('updateMap', type: $this->getTypeString(), json: $this->json, top_districts: $this->top_districts, ranges: $this->ranges ?? null);
            }
        }
    }

    abstract protected function loadRepublicData(): void;

    abstract protected function updateRegionData(DataType $class, string $region): void;
}
