<?php

namespace App\Livewire\Mahallas;

use App\Models\MahallasCode;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Viloyat extends Component
{
    public mixed $result = null;
    public ?string $activeRegion = null;
    public ?string $activeDistrict = null;

    protected $listeners = ['regionClicked'];

    public function mount(): void
    {
        $this->result = MahallasCode::join('mahalla_cluster', 'mahallas_codes.stir', '=', 'mahalla_cluster.stir')
            ->select(
                'mahallas_codes.region_code',
                DB::raw('COUNT(CASE WHEN mahalla_cluster.cluster = 1 THEN 1 END) as cluster1'),
                DB::raw('COUNT(CASE WHEN mahalla_cluster.cluster = 2 THEN 1 END) as cluster2'),
                DB::raw('COUNT(CASE WHEN mahalla_cluster.cluster = 3 THEN 1 END) as cluster3'),
                DB::raw('COUNT(CASE WHEN mahalla_cluster.cluster = 4 THEN 1 END) as cluster4'),
                DB::raw('COUNT(CASE WHEN mahalla_cluster.cluster = 5 THEN 1 END) as cluster5'),
                DB::raw('COUNT(mahallas_codes.id) as total_mahallas')
            )
            ->groupBy('mahallas_codes.region_code')
            ->orderBy('mahallas_codes.region_code')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.mahallas.viloyat');
    }

    public function getRegionOverlay(): array
    {
        $overlay = [];
        foreach ($this->result as $region) {
            $clusters = [
                1 => $region['cluster1'],
                2 => $region['cluster2'],
                3 => $region['cluster3'],
                4 => $region['cluster4'],
                5 => $region['cluster5'],
            ];
            $overlay[(string) $region->region_code] = array_keys($clusters, max($clusters))[0];
        }
        return $overlay;
    }

    public function regionClicked(string $region_code): void
    {
        $this->activeRegion = $region_code;

        $this->result = MahallasCode::join('mahalla_cluster', 'mahallas_codes.stir', '=', 'mahalla_cluster.stir')
            ->select(
                'mahallas_codes.district_code',
                DB::raw('COUNT(CASE WHEN mahalla_cluster.cluster = 1 THEN 1 END) as cluster1'),
                DB::raw('COUNT(CASE WHEN mahalla_cluster.cluster = 2 THEN 1 END) as cluster2'),
                DB::raw('COUNT(CASE WHEN mahalla_cluster.cluster = 3 THEN 1 END) as cluster3'),
                DB::raw('COUNT(CASE WHEN mahalla_cluster.cluster = 4 THEN 1 END) as cluster4'),
                DB::raw('COUNT(CASE WHEN mahalla_cluster.cluster = 5 THEN 1 END) as cluster5'),
                DB::raw('COUNT(mahallas_codes.id) as total_mahallas')
            )
            ->where('mahallas_codes.region_code', $this->activeRegion)
            ->groupBy('mahallas_codes.district_code')
            ->orderBy('mahallas_codes.district_code')
            ->get();

        $overlay = [];
        foreach ($this->result as $district) {
            $clusters = [
                1 => $district['cluster1'],
                2 => $district['cluster2'],
                3 => $district['cluster3'],
                4 => $district['cluster4'],
                5 => $district['cluster5'],
            ];
            $overlay[$district->district_code] = array_keys($clusters, max($clusters))[0];
        }

        $geoJsonUrl = asset('geojson/mahalla/' . $this->activeRegion . '/' . $this->activeRegion . '.geojson');
        $this->dispatch('updateMap', overlay: $overlay, geoJsonUrl: $geoJsonUrl);
    }
}
