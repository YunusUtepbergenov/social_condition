<?php

namespace App\Livewire;

use App\Abstracts\DataType;
use App\Models\{BsScore, BsScorePrediction, Cluster, ClusterDistance, DistrictCluster, Merged, MergedOrg, Protest, ProtestPrediction, Range};
use App\Types\{ClusterType, IndicatorType, MoodType, ProtestType};
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB, Schema};
use Livewire\Component;

class Vizual extends Component
{
    public ?string $active_tum = null;
    public ?Collection $indicators = null;
    public ?string $activeIndicator = null;
    public string $activeRegion = 'republic';
    public mixed $data = null;
    public ?array $json = null;
    public mixed $ranges = null;
    public mixed $clusters = null;
    public string $indicatorClass = 'highlightRed';
    public ?string $date = null;
    public array $dates = [];
    public string $type = 'mood';
    public ?array $columns = null;
    public mixed $top_districts = null;
    public array $monthlyAvg = [];
    public array $actualAvg = [];

    protected $listeners = ['radioType', 'regionClicked', 'dateChanged', 'indicatorChanged', 'regionChanged', 'showChartModal'];

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

    public function mount(): void
    {
        $this->date = $this->getLatesDate();
        $this->dates = $this->getDates();

        $this->ranges = MoodType::getRanges($this->date);

        $this->monthlyAvg = $this->getAverage(BsScorePrediction::class);
        $this->actualAvg = $this->getAverage(BsScore::class, 'bs_score_cur');
        $this->columns = Cache::remember("columns", 600 * 600, function () {
            return Schema::getColumnListing('merged_org');
        });

        $this->top_districts = $this->checkClass()->getTopDistricts($this->activeRegion, $this->activeIndicator, $this->date);

        $this->makeGeoJson();
    }

    public function showChartModal(string $date): void
    {
        $this->dispatch('showReasonModal', date: $date, activeReg: $this->activeRegion, activeTum: $this->active_tum);
        if ($this->active_tum === null) {
            $this->regionChanged($this->activeRegion);
        } else {
            $this->regionClicked($this->active_tum);
        }
    }

    private function getAverage(string $model, string $column = 'score'): array
    {
        return $model::with('district')
            ->select('date', DB::raw("AVG($column) as average"))
            ->whereIn('date', $this->dates)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('average')
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.vizual');
    }

    public function openModal(string $feature): void
    {
        if (in_array($feature, $this->columns)) {
            $tum_pop = intval(Merged::select('demography_population as population')->where('date', $this->date)->where('district_code', $this->active_tum)->first()->population);
            $population = intval(Merged::select(DB::raw('SUM(demography_population) as population'))->where('date', $this->date)->groupBy('date')->first()->population);

            if ($this->type != 'indicator') {
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

    public function clusterModal(string $feature): void
    {
        $data = ClusterDistance::select(DB::raw('value as score'), 'date')->where('indicator', $feature)->where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray();

        $this->dispatch('showClusterModal', feature: $feature, district: $this->active_tum, data: $data, date: $this->date, dates: $this->dates);
        $this->regionClicked($this->active_tum);
    }

    public function regionChanged(string $region): void
    {
        $this->dispatch('regionSelected', region: $region);
        $this->activeRegion = $region;
        $this->active_tum = null;
        $this->indicators = null;
        $class = $this->checkClass();

        $this->top_districts = $class->getTopDistricts($this->activeRegion, $this->activeIndicator, $this->date);

        if ($region !== 'republic') {
            $this->updateRegionData($class, $region);
        } else {
            $this->dateChanged($this->date);
        }

        $this->makeGeoJson();
        $this->dispatch('updateMap', type: $this->type, json: $this->json, top_districts: $this->top_districts, ranges: $this->ranges);
    }

    private function updateRegionData(DataType $class, string $region): void
    {
        if (in_array($this->type, ['mood', 'protests', 'indicator'])) {
            $firstParam = $class->getRegionPredicts($region, $this->date);
            $secondParam = $class->getRegionData($region, $this->date);
            $participants = $class->getRegionParticipants($region, $this->date);
            $this->dispatch('updateChart', dates: $this->dates, data: $firstParam, actual: $secondParam, participants: $participants, type: $this->type);
        } elseif ($this->type === 'clusters') {
            $this->updateClusterData($region);
        }
    }

    private function updateClusterData(string $region): void
    {
        $this->calcClusters();
        $data = DistrictCluster::selectRaw('date, cluster_id, COUNT(*) as total')
            ->where('district_code', 'Like', $region . '%')
            ->groupBy('date', 'cluster_id')
            ->orderBy('date')
            ->get();

        $total = DistrictCluster::selectRaw('date, COUNT(*) as total')
            ->where('district_code', 'Like', $region . '%')
            ->groupBy('date')
            ->pluck('total', 'date');

        $percentages = $data->map(function ($item) use ($total) {
            $item->percentage = ($item->total / $total[$item->date]) * 100;
            return $item;
        });

        $this->dispatch('updateClusterChart', dates: $this->dates, percentages: $percentages, type: $this->type);
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
                        $indicatorSum = MergedOrg::select('date', DB::raw('AVG(' . $this->activeIndicator . ') as sum'))->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('sum')->toArray();
                    } else {
                        $indicatorSum = MergedOrg::select('date', DB::raw('SUM(' . $this->activeIndicator . ') as sum'))->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('sum')->toArray();
                    }
                    $this->top_districts = MergedOrg::with('district')->select(['district_code', 'district_name', DB::raw($indicator . ' as score')])->where('date', $this->date)->orderByRaw('score DESC nulls last')->get();
                    $this->makeGeoJson();

                    $this->dispatch('updateChart', dates: $this->dates, data: $indicatorSum, actual: [], participants: [], type: $this->type);
                    $this->dispatch('updateMap', type: $this->type, json: $this->json, top_districts: $this->top_districts, ranges: $this->ranges);
                }
            }
        }
    }

    public function radioType(string $value, string $indicator): void
    {
        $this->type = $value;
        $this->active_tum = null;
        $this->indicators = null;
        $this->activeRegion = 'republic';
        $this->activeIndicator = $indicator;
        $this->date = $this->getLatesDate();
        $this->dates = $this->getDates();
        $this->dateChanged($this->date);
        $this->dispatch('changeMonths', dates: $this->dates);
        $this->makeGeoJson();
        $this->dispatch('componentLoaded');
    }

    public function regionClicked(string $tuman): void
    {
        $participants = [];
        $actual_avg = [];
        if ($this->type != 'clusters') {
            $record = Merged::select('demography_population as population')->where('date', $this->date)->where('district_code', $tuman)->first();
            if ($record?->population === null) {
                return;
            }
            $population = intval(Merged::select(DB::raw('SUM(demography_population) as population'))->where('date', $this->date)->groupBy('date')->first()->population);
            $tum_pop = intval($record->population);
        }

        $class = $this->checkClass();
        $this->top_districts = $class->getTopDistricts($this->activeRegion, $this->activeIndicator, $this->date);
        $this->active_tum = $tuman;
        $tum_avg = $this->getTumAvg();
        $actual_avg = $this->getTumActualAvg();
        if ($this->type == 'mood') {
            $label = $class->getLabel($this->date, $this->active_tum);
            $this->indicatorClass = ($label == 1) ? 'highlightRed' : 'highlightGreen';
            $this->indicators = $class->getIndicators($tuman, $this->date, $population, $tum_pop, $this->avg_indicators);
        } elseif ($this->type == 'protests') {
            $this->indicatorClass = (end($tum_avg) >= 10) ? 'highlightRed' : 'highlightGreen';
            $participants = Protest::where('district_code', $tuman)->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('participants')->toArray();
            $this->indicators = $class->getIndicators($tuman, $this->date, $population, $tum_pop, $this->avg_indicators);
        } elseif ($this->type == 'indicator') {
            $this->indicatorClass = '';
            $this->indicators = $class->getIndicators($tuman, $this->date, $population, $tum_pop, $this->avg_indicators);
        } elseif ($this->type == 'clusters') {
            $this->indicatorClass = '';
            $this->calcClusters();
            $this->indicators = ClusterDistance::where('district_code', $tuman)->where('date', $this->date)->orderBy('distance', 'ASC')->get();
        }

        $this->dispatch('changeTable', tuman: $tuman, data: $tum_avg, actual: $actual_avg, participants: $participants, dates: $this->dates, date: $this->date, type: $this->type);
    }

    public function dateChanged(string $date): void
    {
        $this->date = $date;
        $participants = [];
        if ($this->type == 'mood') {
            $this->ranges = MoodType::getRanges($this->date);
        } elseif ($this->type == 'clusters') {
            $this->clusters = Cluster::with('clusters')->orderBy('name', 'ASC')->get();
        }
        $this->dates = $this->getDates();

        $class = $this->checkClass();

        if ($this->active_tum) {
            $this->regionClicked($this->active_tum);
            $this->makeGeoJson();
            $this->dispatch('updateMap', type: $this->type, json: $this->json, top_districts: $this->top_districts, ranges: $this->ranges);
        } else {
            if ($this->activeRegion != 'republic') {
                $this->regionChanged($this->activeRegion);
            } else {
                $this->top_districts = $class->getTopDistricts($this->activeRegion, $this->activeIndicator, $this->date);
                if ($this->type == "indicator") {
                    $indicatorSum = $class->getRepublicData(in_array($this->activeIndicator, $this->avg_indicators));
                    $this->dispatch('updateChart', dates: $this->dates, data: $indicatorSum, actual: [], participants: [], type: $this->type);
                } elseif ($this->type == "mood") {
                    $monthlyAvg1 = BsScorePrediction::select('date', DB::raw('AVG(score) as average'))->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
                    $this->actualAvg = BsScore::select('date', DB::raw('AVG(bs_score_cur) as average'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
                    $this->dispatch('updateChart', dates: $this->dates, data: $monthlyAvg1, actual: $this->actualAvg, participants: $participants, type: $this->type);
                } elseif ($this->type == 'protests') {
                    $monthlyAvg1 = ProtestPrediction::select('date', DB::raw('AVG(prediction) as average'))->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
                    $this->actualAvg = Protest::select('date', DB::raw('SUM(count) as average'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
                    $participants = Protest::select('date', DB::raw('SUM(participants) as score'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('score')->toArray();

                    $this->dispatch('updateChart', dates: $this->dates, data: $monthlyAvg1, actual: $this->actualAvg, participants: $participants, type: $this->type);
                } elseif ($this->type == "clusters") {
                    $this->calcClusters();

                    $data = DistrictCluster::select(['date', 'cluster_id', DB::raw('COUNT(*) as total')])->groupBy('date', 'cluster_id')->orderBy('date', 'ASC')->get();
                    $total = DistrictCluster::select(['date', DB::raw('COUNT(*) as total')])->groupBy('date')->get();
                    $percentages = $data->map(function ($item) use ($total) {
                        $totalForMonth = $total->firstWhere('date', $item->date)->total;
                        $item->percentage = ($item->total / $totalForMonth) * 100;
                        return $item;
                    });
                    $this->dispatch('updateClusterChart', dates: $this->dates, percentages: $percentages, type: $this->type);
                }
                $this->makeGeoJson();
                $this->dispatch('updateMap', type: $this->type, json: $this->json, top_districts: $this->top_districts, ranges: $this->ranges);
            }
        }
    }

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

    public function checkClass(): DataType
    {
        return match ($this->type) {
            'mood' => new MoodType(),
            'protests' => new ProtestType(),
            'indicator' => new IndicatorType($this->activeIndicator),
            'clusters' => new ClusterType(),
        };
    }

    public function getDates(): array
    {
        return match ($this->type) {
            'indicator' => MergedOrg::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->pluck('date')->toArray(),
            'mood' => BsScorePrediction::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->pluck('date')->toArray(),
            'protests' => ProtestPrediction::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->pluck('date')->toArray(),
            'clusters' => DistrictCluster::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->pluck('date')->toArray(),
            default => [],
        };
    }

    public function getLatesDate(): ?string
    {
        return match ($this->type) {
            'indicator' => MergedOrg::orderBy('date', 'DESC')->first()?->date,
            'mood' => BsScorePrediction::orderBy('date', 'DESC')->first()?->date,
            'protests' => ProtestPrediction::orderBy('date', 'DESC')->first()?->date,
            'clusters' => DistrictCluster::orderBy('date', 'DESC')->first()?->date,
            default => null,
        };
    }

    public function getTumAvg(): array
    {
        $data = match ($this->type) {
            'indicator' => MergedOrg::select(DB::raw($this->activeIndicator . ' as score'), 'date')->where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray(),
            'mood' => BsScorePrediction::where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray(),
            'protests' => ProtestPrediction::select('prediction as score', 'date')->where('district_code', $this->active_tum)->whereDate('date', '<=', $this->date)->orderBy('date')->get()->pluck('score', 'date')->toArray(),
            'clusters' => DistrictCluster::select(DB::raw('cluster_id as score, date'))->where('district_code', $this->active_tum)->where('date', '<=', $this->date)->orderBy('date')->get()->pluck('score', 'date')->toArray(),
            default => [],
        };

        $dates = array_fill_keys($this->dates, null);
        return ($this->type == 'clusters') ? $data : array_merge($dates, $data);
    }

    public function getTumActualAvg(): array
    {
        $dates = array_fill_keys($this->dates, null);

        return match ($this->type) {
            'mood' => array_merge($dates, BsScore::where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('bs_score_cur', 'date')->toArray()),
            'protests' => array_values(array_merge($dates, Protest::where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date', 'ASC')->get()->pluck('count', 'date')->toArray())),
            default => [],
        };
    }

    public function calcClusters(): void
    {
        $this->clusters = Cluster::with(['clusters' => function ($query) {
            $query->where('date', $this->date)
                ->when($this->activeRegion != 'republic', function ($q) {
                    return $q->where('district_code', 'like', $this->activeRegion . '%');
                })
                ->orderByDesc('order');
        }])->orderBy('name', 'ASC')->get();

        $previousClusters = DistrictCluster::where('date', $this->date - 1)->pluck('cluster_id', 'district_code');

        foreach ($this->clusters as $cluster) {
            foreach ($cluster->clusters as $data) {
                if (isset($previousClusters[$data->district_code])) {
                    $data->diff = $previousClusters[$data->district_code] - $data->cluster_id;
                }
            }
        }
    }

    public function calcAvg(float $n, float $tum_pop): float
    {
        return $n * $tum_pop * 100000;
    }
}
