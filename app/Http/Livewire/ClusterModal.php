<?php

namespace App\Http\Livewire;

use App\Models\ClusterDistance;
use App\Models\Merged;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ClusterModal extends Component
{
    protected $listeners = ['showClusterModal'];

    public $activeIndicator, $activeDistrict, $date;
    public $repAvg, $vilAvg, $curVal;
    public $ovrReg, $ovrRep;
    public $repAvgNor, $vilAvgNor, $curValNor, $lastYear;

    public function render()
    {
        return view('livewire.cluster-modal');
    }

    public function showClusterModal($feature, $district, $data,$date, $dates){
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
                                    ['district_code', 'LIKE', substr($district, 0, 4).'%'],
                                ])->groupBY('date')->first();

        $this->lastYear = $data[intval($date) - 1];

        $this->ovrReg = ClusterDistance::select(DB::raw('SUM(value) as feature'))
                                ->where('indicator', $feature)
                                ->where('district_code', 'Like', $regionCode.'%')
                                ->where('date', $date)
                                ->first();

        $this->ovrRep = ClusterDistance::select(DB::raw('SUM(value) as feature'))
                                ->where('indicator', $feature)
                                ->where('date', $date)
                                ->first();

        $this->dispatchBrowserEvent('openClusterModal');
        $this->emit('buildClusterChart', $data, $dates);
    }
}
