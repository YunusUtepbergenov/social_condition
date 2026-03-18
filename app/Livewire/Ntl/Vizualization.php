<?php

namespace App\Livewire\Ntl;

use App\Models\Cluster;
use App\Models\NtlData;
use App\Models\Range;
use App\Types\Ntl;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Vizualization extends Component
{
    public mixed $vil_val = null;
    public ?string $active_tum = null;
    public mixed $indicators = null;
    public ?string $activeIndicator = null;
    public string $activeRegion = 'republic';
    public mixed $data = null;
    public ?array $json = null;
    public mixed $ranges = null;
    public mixed $clusters = null;
    public ?string $date = null;
    public ?string $type = null;
    public mixed $top_districts = null;
    public array $dates = [];
    public array $monthlyAvg = [];
    public array $actualAvg = [];
    public ?array $columns = null;

    public function mount(): void
    {
        $this->clusters = Cluster::with('clusters')->orderBy('name', 'ASC')->get();
        $this->ranges = Range::where('date', $this->date)->get();

        $this->type = 'clusters';
        $this->date = $this->getLatesDate();
        $this->dates = $this->getDates();

        $this->top_districts = (new Ntl())->getTopDistricts($this->activeRegion, null, $this->date);
        $this->monthlyAvg = NtlData::with('district')->select('date', DB::raw('AVG(ntl_mean) as average'))->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->calcClusters();
        $this->makeGeoJson();
    }

    public function render(): View
    {
        return view('livewire.ntl.vizualization');
    }

    public function makeGeoJson(): void
    {
        $path = public_path('geojson\districts.json');
        $this->json = json_decode(file_get_contents($path), true);

        foreach ($this->top_districts as $district) {
            foreach ($this->json['features'] as $key => $feature) {
                if ($district->district_code == $feature['properties']['district_code']) {
                    $this->json['features'][$key]['factors']['score'] = $district->cluster_ascending;
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
        return NtlData::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray();
    }

    public function getLatesDate(): string
    {
        return NtlData::orderBy('date', 'DESC')->first()->date;
    }

    public function calcClusters(): void
    {
        $this->clusters = Cluster::with('clusters')->orderBy('name', 'ASC')->get();
        if ($this->activeRegion == 'republic') {
            foreach ($this->clusters as $cluster) {
                $cluster->ntl = $cluster->ntl->where('date', $this->date);
                $cluster->ntl = $cluster->ntl->sortByDesc('order')->values()->all();
            }
        } else {
            foreach ($this->ntl as $cluster) {
                $cluster->ntl = $cluster->clusters->where('date', $this->date)
                    ->filter(function (NtlData $value): bool {
                        return str_starts_with($value->district_code, $this->activeRegion);
                    })->sortByDesc('order')
                    ->values();
            }
        }
    }
}
