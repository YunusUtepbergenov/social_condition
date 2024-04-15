<?php

namespace App\Http\Livewire\Ntl;

use App\Models\NtlData;
use Carbon\Carbon;
use Livewire\Component;

class Timeline extends Component
{

    public $months = [];

    protected $listeners = ['changeTimeline'];

    public function mount(){
        $latestDate = NtlData::max('date');

        $this->months = NtlData::select('date')
                                            ->distinct('date')
                                            ->whereBetween('date', [Carbon::parse($latestDate)->subMonth(23), $latestDate])
                                            ->orderBy('date', 'ASC')
                                            ->get()
                                            ->pluck('date')
                                            ->toArray();
    }

    public function changeTimeline($dates){
        $this->months = array_slice($dates, -24);
        $this->emit('changeNtlTimeline', $this->months);
    }

    public function render()
    {
        return view('livewire.ntl.timeline');
    }
}
