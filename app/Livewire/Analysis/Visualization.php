<?php

namespace App\Livewire\Analysis;

use App\Models\BsScore;
use App\Models\BsScorePrediction;
use App\Models\Cluster;
use App\Models\ClusterDistance;
use App\Models\DistrictCluster;
use App\Models\Merged;
use App\Models\MergedOrg;
use App\Models\MiProtest;
use App\Models\MutualInfo;
use App\Models\Protest;
use App\Models\ProtestPrediction;
use App\Models\Range;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Visualization extends Component
{
    public mixed $vil_val = null;
    public ?string $active_tum = null;
    public mixed $indicators = null;
    public ?string $activeIndicator = null;
    public string $activeRegion = 'republic';
    public mixed $data = null;
    public ?array $json = null;
    public ?string $date = null;
    public mixed $top_districts = null;
    public array $dates = [];
    public array $monthlyAvg = [];
    public array $actualAvg = [];
    public string $type = 'mood';
    public mixed $sum = null;
    public mixed $ranges = null;
    public mixed $clusters = null;

    protected $listeners = ['radioType', 'regionClicked', 'dateChanged', 'indicatorChanged', 'regionChanged'];

    public array $avg_indicators = [
        'weather_temperature', 'weather_precipitation', 'weather_pollution',
        'weather_wind', 'weather_pressure', 'weather_humidity',
        'electr_population_price', 'electr_pop_nogas_price', 'electr_other_price',
        'electr_budget_price', 'electr_public_utilities_price', 'electr_industry_price',
        'electr_сommercial_price', 'electr_agriculture_price', 'electr_transport_construction_price',
        'sug_population_price', 'sug_mtm_price', 'sug_military_price', 'sug_forest_price'
    ];

    public function mount(): void
    {
        $this->clusters = Cluster::with('clusters')->orderBy('name', 'ASC')->get();
        $this->date = $this->getLatesDate();
        $this->dates = $this->getDates();
        $this->ranges = Range::where('date', $this->date)->get();
        $this->monthlyAvg = BsScorePrediction::with('district')->select('date', DB::raw('AVG(score) as average'))->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->actualAvg = BsScore::with('district')->select('date', DB::raw('AVG(bs_score_cur) as average'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();

        $this->top_districts = $this->getTopDistricts();
        $this->makeGeoJson();
    }

    public function render(): View
    {
        return view('livewire.analysis.visualization');
    }

    public function openModal(string $feature): void
    {
        $tum_pop = intval(Merged::select('demography_population as population')->where('date', $this->date)->where('district_code', $this->active_tum)->first()?->population ?? 0);
        $population = intval(Merged::select(DB::raw('SUM(demography_population) as population'))->where('date', $this->date)->groupBy('date')->first()?->population ?? 0);

        $data = Merged::select(DB::raw($feature . ' as score'), 'date')->where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray();
        $dataAvg = ($tum_pop > 0)
            ? Merged::select(DB::raw($feature . ' / ' . $tum_pop . '*' . 100000 . ' as score'), 'date')->where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score')->toArray()
            : [];

        $this->dispatch('showInfoModal', feature: $feature, district: $this->active_tum, data: $data, dataAvg: $dataAvg, date: $this->date, dates: $this->dates, population: $population, tum_pop: $tum_pop);
        $this->regionClicked($this->active_tum);
    }

    public function regionChanged(string $region): void
    {
        $this->dispatch('regionSelected', region: $region);
        $this->activeRegion = $region;
        $this->active_tum = null;
        $this->indicators = null;
        $this->top_districts = $this->getTopDistricts();

        if ($this->type == 'mood') {
            if ($region == 'republic') {
                $this->dateChanged($this->date);
            } else {
                $predictionAvg = BsScorePrediction::select('date', DB::raw('AVG(score) as average'))
                    ->where('district_code', 'LIKE', $region . '%')
                    ->where('date', '<=', $this->date)
                    ->groupBy('date')->orderBy('average')
                    ->get()->pluck('average')
                    ->toArray();

                $actualAvg = BsScore::select('date', DB::raw('AVG(bs_score_cur) as average'))
                    ->where('date', '<=', $this->date)
                    ->where('district_code', 'LIKE', $region . '%')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->pluck('average')
                    ->toArray();
                $this->makeGeoJson();
                $this->dispatch('updateChart', dates: $this->dates, data: $predictionAvg, actual: $actualAvg, participants: [], type: $this->type);
                $this->dispatch('updateMap', type: $this->type, json: $this->json, top_districts: $this->top_districts, ranges: $this->ranges);
            }
        } elseif ($this->type == 'indicator') {
            if ($region == 'republic') {
                $this->indicatorChanged($this->activeIndicator);
            } else {
                $indicatorSum = MergedOrg::select('date', DB::raw('SUM(' . $this->activeIndicator . ') as sum'))
                    ->where('district_code', 'LIKE', $region . '%')
                    ->where('date', '<=', $this->date)
                    ->groupBy('date')->orderBy('date')
                    ->get()->pluck('sum')
                    ->toArray();

                $this->makeGeoJson();
                $this->dispatch('updateChart', dates: $this->dates, data: $indicatorSum, actual: [], participants: [], type: $this->type);
                $this->dispatch('updateMap', type: $this->type, json: $this->json, top_districts: $this->top_districts, ranges: $this->ranges);
            }
        } elseif ($this->type == 'protests') {
            if ($region == 'republic') {
                $this->dateChanged($this->date);
            } else {
                $monthlyAvg1 = ProtestPrediction::select('date', DB::raw('AVG(prediction) as average'))
                    ->where('date', '<=', $this->date)
                    ->where('district_code', 'LIKE', $region . '%')
                    ->groupBy('date')->orderBy('date')
                    ->get()
                    ->pluck('average')
                    ->toArray();

                $this->actualAvg = Protest::select('date', DB::raw('SUM(count) as average'))
                    ->where('date', '<=', $this->date)
                    ->where('district_code', 'LIKE', $region . '%')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->pluck('average')
                    ->toArray();

                $participants = Protest::select('date', DB::raw('SUM(participants) as score'))
                    ->whereIn('date', $this->dates)
                    ->where('district_code', 'LIKE', $region . '%')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->pluck('score')
                    ->toArray();
                $this->makeGeoJson();
                $this->dispatch('updateChart', dates: $this->dates, data: $monthlyAvg1, actual: $this->actualAvg, participants: $participants, type: $this->type);
                $this->dispatch('updateMap', type: $this->type, json: $this->json, top_districts: $this->top_districts, ranges: $this->ranges);
            }
        } elseif ($this->type == 'clusters') {
            if ($region == 'republic') {
                $this->dateChanged($this->date);
            } else {
                $this->calcClusters();

                $data = DistrictCluster::select(['date', 'cluster_id', DB::raw('COUNT(*) as total')])->where('district_code', 'Like', $this->activeRegion . '%')->groupBy('date', 'cluster_id')->orderBy('date', 'ASC')->get();
                $total = DistrictCluster::select(['date', DB::raw('COUNT(*) as total')])->where('district_code', 'Like', $this->activeRegion . '%')->groupBy('date')->get();
                $percentages = $data->map(function ($item) use ($total) {
                    $totalForMonth = $total->firstWhere('date', $item->date)?->total ?? 0;
                    $item->percentage = ($totalForMonth > 0) ? ($item->total / $totalForMonth) * 100 : 0;
                    return $item;
                });
                $this->makeGeoJson();
                $this->dispatch('updateClusterChart', dates: $this->dates, percentages: $percentages, type: $this->type);
                $this->dispatch('updateMap', type: $this->type, json: $this->json, top_districts: $this->top_districts, ranges: $this->ranges);
            }
        }
    }

    public function indicatorChanged(string $indicator): void
    {
        $this->activeIndicator = $indicator;
        if ($this->active_tum) {
            $this->regionClicked($this->active_tum);
        } else {
            $this->dates = $this->getDates();
            if ($this->activeRegion != 'republic') {
                $this->regionChanged($this->activeRegion);
            } else {
                $indicatorSum = MergedOrg::select('date', DB::raw('SUM(' . $this->activeIndicator . ') as sum'))->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('sum')->toArray();
                $this->top_districts = MergedOrg::with('district')->select(['district_code', 'district_name', DB::raw($indicator . ' as score')])->where('date', $this->date)->orderByRaw('score DESC nulls last')->get();
                $this->makeGeoJson();
                $this->dispatch('updateChart', dates: $this->dates, data: $indicatorSum, actual: [], participants: [], type: $this->type);
                $this->dispatch('updateMap', type: $this->type, json: $this->json, top_districts: $this->top_districts, ranges: $this->ranges);
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
        $this->dispatch('regionSelected', region: $this->activeRegion);
    }

    public function regionClicked(string $tuman): void
    {
        $participants = [];
        $actual_avg = [];
        $population = intval(Merged::select(DB::raw('SUM(demography_population) as population'))->where('date', $this->date)->groupBy('date')->first()?->population ?? 0);
        $tum_pop = intval(Merged::select('demography_population as population')->where('date', $this->date)->where('district_code', $tuman)->first()?->population ?? 0);

        $this->top_districts = $this->getTopDistricts();
        $this->active_tum = $tuman;
        $tum_avg = $this->getTumAvg();
        $actual_avg = $this->getTumActualAvg();

        if ($this->type == 'mood') {
            $this->indicators = MutualInfo::where('district_code', $tuman)->whereDate('date', $this->date)->orderBy('mutual_info', 'DESC')->get();

            $this->indicators->map(function ($indicator) use ($population, $tum_pop) {
                if (in_array($indicator->feature_name, $this->avg_indicators)) {
                    $indicator->average = Merged::select(DB::raw('AVG(' . $indicator->feature_name . ') as avg'))->whereDate('date', $this->date)->groupBy('date')->first()?->avg;
                    $indicator->value = Merged::select($indicator->feature_name . ' as indicator')->whereDate('date', $this->date)->where('district_code', $this->active_tum)->first()?->indicator;
                } else {
                    $sumResult = Merged::select(DB::raw('SUM(' . $indicator->feature_name . ') as sum'))->where('date', $this->date)->groupBy('date')->first();
                    $indicator->average = ($population > 0 && $sumResult) ? ($sumResult->sum / $population) * 100000 : null;
                    $valResult = Merged::select($indicator->feature_name . ' as indicator')->where('date', $this->date)->where('district_code', $this->active_tum)->first();
                    $indicator->value = ($tum_pop > 0 && $valResult) ? ($valResult->indicator / $tum_pop) * 100000 : null;
                }
                return $indicator;
            });
        } elseif ($this->type == 'protests') {
            $participants = Protest::where('district_code', $tuman)->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('participants')->toArray();
            $this->indicators = MiProtest::select('feature_name')->where('district_code', $tuman)->whereDate('date', $this->date)->get();

            $this->indicators->map(function ($indicator) use ($population, $tum_pop) {
                if (in_array($indicator->feature_name, $this->avg_indicators)) {
                    $indicator->average = Merged::select(DB::raw('AVG(' . $indicator->feature_name . ') as sum'))->whereDate('date', $this->date)->groupBy('date')->first()?->avg;
                    $indicator->value = Merged::select($indicator->feature_name . ' as indicator')->whereDate('date', $this->date)->where('district_code', $this->active_tum)->first()?->indicator;
                } else {
                    $sumResult = Merged::select(DB::raw('SUM(' . $indicator->feature_name . ') as sum'))->whereDate('date', $this->date)->groupBy('date')->first();
                    $indicator->average = ($population > 0 && $sumResult) ? ($sumResult->sum / $population) * 100000 : null;
                    $valResult = Merged::select($indicator->feature_name . ' as indicator')->whereDate('date', $this->date)->where('district_code', $this->active_tum)->first();
                    $indicator->value = ($tum_pop > 0 && $valResult) ? ($valResult->indicator / $tum_pop) * 100000 : null;
                }
                return $indicator;
            });
        } elseif ($this->type == 'clusters') {
            $this->calcClusters();

            $this->indicators = ClusterDistance::where('district_code', $tuman)->where('date', $this->date)->orderBy('distance', 'ASC')->get();
        }
        $this->dispatch('changeTable', tuman: $tuman, data: $tum_avg, actual: $actual_avg, participants: $participants, dates: $this->dates, date: $this->date, type: $this->type);
    }

    public function dateChanged(string $date): void
    {
        $this->date = $date;
        $participants = [];
        $this->dates = $this->getDates();

        if ($this->active_tum) {
            $this->regionClicked($this->active_tum);
            $this->makeGeoJson();
            $this->dispatch('updateMap', type: $this->type, json: $this->json, top_districts: $this->top_districts, ranges: $this->ranges);
        } else {
            if ($this->activeRegion != 'republic') {
                $this->regionChanged($this->activeRegion);
            } else {
                $this->top_districts = $this->getTopDistricts();
                if ($this->type == "indicator") {
                    $indicatorSum = Merged::select('date', DB::raw('SUM(' . $this->activeIndicator . ') as sum'))
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get()
                        ->pluck('sum')
                        ->toArray();

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

    // ------------------------ HELPER FUNCTIONS ------------------------

    public function makeGeoJson(): void
    {
        $path = public_path('geojson\districts.json');
        $this->json = json_decode(file_get_contents($path), true);

        foreach ($this->top_districts as $district) {
            foreach ($this->json['features'] as $key => $feature) {
                if ($district->district_code == $feature['properties']['district_code']) {
                    $this->json['features'][$key]['factors']['score'] = $district->score;
                    if (isset($district->label)) {
                        $this->json['features'][$key]['factors']['label'] = $district->label;
                    }
                    break;
                }
            }
        }
    }

    public function getDates(): array
    {
        return match ($this->type) {
            'indicator' => MergedOrg::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray(),
            'mood' => BsScorePrediction::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray(),
            'protests' => ProtestPrediction::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray(),
            'clusters' => DistrictCluster::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray(),
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

    public function getTopDistricts(): ?Collection
    {
        return match ($this->type) {
            'indicator' => $this->activeRegion == 'republic'
                ? MergedOrg::with('district')->select(['district_code', 'district_name', DB::raw($this->activeIndicator . ' as score')])->where('date', $this->date)->orderByRaw('score DESC nulls last')->get()
                : MergedOrg::with('district')->select(['district_code', 'district_name', DB::raw($this->activeIndicator . ' as score')])->where('date', $this->date)->where('district_code', 'LIKE', $this->activeRegion . '%')->orderByRaw('score DESC nulls last')->get(),
            'mood' => $this->activeRegion == 'republic'
                ? BsScorePrediction::with('district')->where('date', $this->date)->orderBy('score', 'DESC')->get()
                : BsScorePrediction::with('district')->where('date', $this->date)->where('district_code', 'LIKE', $this->activeRegion . '%')->orderByRaw('score DESC nulls last')->get(),
            'protests' => $this->activeRegion == 'republic'
                ? ProtestPrediction::with('district')->select(['district_code', 'prediction as score'])->where('date', $this->date)->orderByRaw('score DESC nulls last')->get()
                : ProtestPrediction::with('district')->select(['district_code', 'prediction as score'])->where('date', $this->date)->where('district_code', 'LIKE', $this->activeRegion . '%')->orderByRaw('score DESC nulls last')->get(),
            'clusters' => $this->activeRegion == 'republic'
                ? DistrictCluster::with('district')->select(['district_code', 'cluster_id as score'])->where('date', $this->date)->orderBy('score')->get()
                : DistrictCluster::with('district')->select(['district_code', DB::raw('cluster_id as score')])->where('date', $this->date)->where('district_code', 'LIKE', $this->activeRegion . '%')->orderByRaw('score DESC nulls last')->get(),
            default => null,
        };
    }

    public function getTumAvg(): array
    {
        $dates = array_fill_keys($this->dates, null);
        $data = match ($this->type) {
            'indicator' => MergedOrg::select(DB::raw($this->activeIndicator . ' as score'), 'date')->where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray(),
            'mood' => BsScorePrediction::where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray(),
            'protests' => ProtestPrediction::select('prediction as score', 'date')->where('district_code', $this->active_tum)->whereDate('date', '<=', $this->date)->orderBy('date')->get()->pluck('score', 'date')->toArray(),
            'clusters' => DistrictCluster::select(DB::raw('cluster_id as score, date'))->where('district_code', $this->active_tum)->whereDate('date', '<=', $this->date)->orderBy('date')->get()->pluck('score', 'date')->toArray(),
            default => [],
        };
        return array_merge($dates, $data);
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
        $this->clusters = Cluster::with('clusters')->orderBy('name', 'ASC')->get();
        if ($this->activeRegion == 'republic') {
            foreach ($this->clusters as $cluster) {
                $cluster->clusters = $cluster->clusters->where('date', $this->date);
                $cluster->clusters = $cluster->clusters->sortByDesc('order')->values()->all();
                foreach ($cluster->clusters as $data) {
                    $val = DistrictCluster::where([
                        'district_code' => $data->district_code,
                        'date' => Carbon::parse($data->date)->subYear(1),
                    ])->first();
                    if ($val !== null) {
                        $data->diff = $val->cluster_id - $data->cluster_id;
                    }
                }
            }
        } else {
            foreach ($this->clusters as $cluster) {
                $cluster->clusters = $cluster->clusters->where('date', $this->date)
                    ->filter(function (DistrictCluster $value): bool {
                        return str_starts_with($value->district_code, $this->activeRegion);
                    })->sortByDesc('order')
                    ->values();

                foreach ($cluster->clusters as $data) {
                    $val = DistrictCluster::where([
                        'district_code' => $data->district_code,
                        'date' => Carbon::parse($data->date)->subYear(1),
                    ])->first();
                    if ($val !== null) {
                        $data->diff = $val->cluster_id - $data->cluster_id;
                    }
                }
            }
        }
    }
}
