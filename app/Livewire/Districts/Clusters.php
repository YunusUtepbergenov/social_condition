<?php

namespace App\Livewire\Districts;

use App\Abstracts\DataType;
use App\Livewire\Concerns\HasMapVisualization;
use App\Models\{Cluster, ClusterDistance, DistrictCluster};
use App\Types\ClusterType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Clusters extends Component
{
    use HasMapVisualization;

    public mixed $clusters = null;
    public mixed $indicators = null;
    public array $clusterPercentages = [];

    protected $listeners = ['regionClicked', 'dateChanged', 'regionChanged'];

    public function mount(): void
    {
        $this->date = $this->getLatestDate();
        $this->dates = $this->getDates();
        $this->top_districts = $this->getDataClass()->getTopDistricts($this->activeRegion, null, $this->date);
        $this->calcClusters();
        $this->makeGeoJson();
        $this->clusterPercentages = $this->getClusterPercentages();
        $this->dispatch('changeMonths', dates: $this->dates);
    }

    public function render(): View
    {
        return view('livewire.districts.clusters');
    }

    public function getTypeString(): string
    {
        return 'clusters';
    }

    public function getDataClass(): DataType
    {
        return new ClusterType();
    }

    public function getDates(): array
    {
        return DistrictCluster::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->pluck('date')->toArray();
    }

    public function getLatestDate(): ?string
    {
        return DistrictCluster::orderBy('date', 'DESC')->first()?->date;
    }

    public function getTumAvg(): array
    {
        $data = DistrictCluster::select(DB::raw('cluster_id as score, date'))->where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray();
        $dates = array_fill_keys($this->dates, null);
        return array_values(array_merge($dates, $data));
    }

    public function getTumActualAvg(): array
    {
        return [];
    }

    public function loadDateData(): void
    {
        $this->clusters = Cluster::with('clusters')->orderBy('name', 'ASC')->get();
    }

    public function regionClicked(string $tuman): void
    {
        $class = $this->getDataClass();
        $this->top_districts = $class->getTopDistricts($this->activeRegion, null, $this->date);
        $this->active_tum = $tuman;

        $tum_avg = $this->getTumAvg();

        $this->indicatorClass = '';
        $this->calcClusters();
        $this->indicators = ClusterDistance::where('district_code', $tuman)->where('date', $this->date)->orderBy('distance', 'ASC')->get();

        $this->dispatch('changeTable', tuman: $tuman, data: $tum_avg, actual: [], participants: [], dates: $this->dates, date: $this->date, type: $this->getTypeString());
    }

    public function clusterModal(string $feature): void
    {
        $data = array_values(ClusterDistance::select(DB::raw('value as score'), 'date')->where('indicator', $feature)->where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray());

        $this->dispatch('showClusterModal', feature: $feature, district: $this->active_tum, data: $data, date: $this->date, dates: $this->dates);
        $this->regionClicked($this->active_tum);
    }

    public function loadRegionClickedData(string $tuman): void
    {
        // Handled in regionClicked directly
    }

    protected function getClusterPercentages(?string $regionPrefix = null): array
    {
        $query = DistrictCluster::select(['date', 'cluster_id', DB::raw('COUNT(*) as total')])
            ->groupBy('date', 'cluster_id')
            ->orderBy('date', 'ASC');

        $totalQuery = DistrictCluster::select(['date', DB::raw('COUNT(*) as total')])
            ->groupBy('date');

        if ($regionPrefix) {
            $query->where('district_code', 'Like', $regionPrefix . '%');
            $totalQuery->where('district_code', 'Like', $regionPrefix . '%');
        }

        $data = $query->get();
        $total = $totalQuery->get();

        return $data->map(function ($item) use ($total) {
            $totalForMonth = $total->firstWhere('date', $item->date)?->total ?? 0;
            $item->percentage = ($totalForMonth > 0) ? ($item->total / $totalForMonth) * 100 : 0;
            return $item;
        })->toArray();
    }

    protected function loadRepublicData(): void
    {
        $this->calcClusters();
        $percentages = $this->getClusterPercentages();
        $this->dispatch('updateClusterChart', dates: $this->dates, percentages: $percentages, type: $this->getTypeString());
    }

    protected function updateRegionData(DataType $class, string $region): void
    {
        $this->calcClusters();
        $percentages = $this->getClusterPercentages($region);
        $this->dispatch('updateClusterChart', dates: $this->dates, percentages: $percentages, type: $this->getTypeString());
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
}
