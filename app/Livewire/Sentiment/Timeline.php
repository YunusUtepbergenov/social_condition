<?php

namespace App\Livewire\Sentiment;

use App\Models\Sentiment;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Timeline extends Component
{
    public array $months = [];

    protected $listeners = ['changeMonths'];

    public function mount(): void
    {
        $this->months = Sentiment::select('date')
            ->distinct('date')
            ->orderBy('date', 'ASC')
            ->get()
            ->pluck('date')
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.sentiment.timeline');
    }

    public function changeMonths(array $dates): void
    {
        $this->months = array_slice($dates, -24);
        $this->dispatch('changeSentimentTimeline', dates: $this->months);
    }
}
