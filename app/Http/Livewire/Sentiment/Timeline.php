<?php

namespace App\Http\Livewire\Sentiment;

use App\Models\Sentiment;
use Livewire\Component;

class Timeline extends Component
{
    public $months = [];

    protected $listeners = ['changeMonths'];

    public function mount(){
        $this->months = Sentiment::select('date')
                                    ->distinct('date')
                                    ->orderBy('date', 'ASC')
                                    ->get()
                                    ->pluck('date')
                                    ->toArray();
    }
    public function render()
    {
        return view('livewire.sentiment.timeline');
    }

    public function changeMonths($dates){
        $this->months = array_slice($dates, -24);
        $this->emit('changeTimeline', $this->months);
    }
}
