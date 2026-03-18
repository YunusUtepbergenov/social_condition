<?php

namespace App\Livewire\Analysis;

use App\Models\BsScore;
use App\Models\BsScorePrediction;
use Carbon\Carbon;
use Livewire\Component;

class Timeline extends Component
{
    public $months = [];

    protected $listeners = ['changeMonths'];

    public function mount(){
        $latestDate = BsScorePrediction::max('date');

        $this->months = BsScorePrediction::select('date')
                                            ->distinct('date')
                                            ->whereBetween('date', [Carbon::parse($latestDate)->subMonth(23), $latestDate])
                                            ->orderBy('date', 'ASC')
                                            ->get()
                                            ->pluck('date')
                                            ->toArray();
    }

    public function render()
    {
        return view('livewire.analysis.timeline');
    }

    public function changeMonths($dates){
        $this->months = array_slice($dates, -24);
        $this->dispatch('changeTimeline', dates: $this->months);
    }
}
