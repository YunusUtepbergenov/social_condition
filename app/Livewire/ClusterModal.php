<?php

namespace App\Livewire;

use App\Models\ClusterDistance;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ClusterModal extends Component
{
    protected $listeners = ['showClusterModal'];

    public function toJSON(): array
    {
        return [];
    }

    public ?string $activeIndicator = null;
    public ?string $activeDistrict = null;
    public ?string $date = null;
    public mixed $repAvg = null;
    public mixed $vilAvg = null;
    public mixed $curVal = null;
    public mixed $ovrReg = null;
    public mixed $ovrRep = null;
    public mixed $repAvgNor = null;
    public mixed $vilAvgNor = null;
    public mixed $curValNor = null;
    public mixed $lastYear = null;

    public function render(): View
    {
        return view('livewire.cluster-modal');
    }

    public function showClusterModal(string $feature, string $district, array $data, string $date, array $dates): void
    {
        $this->activeDistrict = $district;
        $regionCode = substr($district, 0, 4);
        $this->activeIndicator = $feature;
        $this->date = $date;

        $this->curVal = end($data);

        $this->repAvg = ClusterDistance::select(DB::raw('AVG(value) as score'))
            ->where('indicator', $feature)
            ->where('date', '=', $date)
            ->groupBY('date')
            ->first();

        $this->vilAvg = ClusterDistance::select(DB::raw('AVG(value) as score'))
            ->where([
                ['indicator', $feature],
                ['date', '=', $date],
                ['district_code', '>=', intval(substr($district, 0, 4)) * 1000],
                ['district_code', '<', (intval(substr($district, 0, 4)) + 1) * 1000],
            ])->groupBY('date')->first();

        $this->lastYear = $data[intval($date) - 1];

        $this->ovrReg = ClusterDistance::select(DB::raw('SUM(value) as feature'))
            ->where('indicator', $feature)
            ->where(fn($q) => whereDistrictPrefix($q, $regionCode))
            ->where('date', $date)
            ->first();

        $this->ovrRep = ClusterDistance::select(DB::raw('SUM(value) as feature'))
            ->where('indicator', $feature)
            ->where('date', $date)
            ->first();

        $this->dispatch('openClusterModal');
        $this->dispatch('buildClusterChart', data: $data, dates: $dates);
    }
}
