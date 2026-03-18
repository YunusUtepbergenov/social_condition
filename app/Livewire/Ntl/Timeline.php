<?php

namespace App\Livewire\Ntl;

use App\Models\NtlData;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Timeline extends Component
{
    public array $months = [];

    protected $listeners = ['changeTimeline'];

    public function mount(): void
    {
        $latestDate = NtlData::max('date');

        $this->months = NtlData::select('date')
            ->distinct('date')
            ->whereBetween('date', [Carbon::parse($latestDate)->subMonth(23), $latestDate])
            ->orderBy('date', 'ASC')
            ->get()
            ->pluck('date')
            ->toArray();
    }

    public function changeTimeline(array $dates): void
    {
        $this->months = array_slice($dates, -24);
        $this->dispatch('changeNtlTimeline', dates: $this->months);
    }

    public function render(): View
    {
        return view('livewire.ntl.timeline');
    }
}
