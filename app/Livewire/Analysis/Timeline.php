<?php

namespace App\Livewire\Analysis;

use App\Models\BsScorePrediction;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Timeline extends Component
{
    public array $months = [];

    protected $listeners = ['changeMonths'];

    public function mount(): void
    {
        $latestDate = BsScorePrediction::max('date');

        $this->months = BsScorePrediction::select('date')
            ->distinct('date')
            ->whereBetween('date', [Carbon::parse($latestDate)->subMonth(23), $latestDate])
            ->orderBy('date', 'ASC')
            ->get()
            ->pluck('date')
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.analysis.timeline');
    }

    public function changeMonths(array $dates): void
    {
        $this->months = array_slice($dates, -24);
        $this->dispatch('changeTimeline', dates: $this->months);
    }
}
