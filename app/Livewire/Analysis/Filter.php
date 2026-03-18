<?php

namespace App\Livewire\Analysis;

use App\Models\Region;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Filter extends Component
{
    public string $radio = 'mood';
    public mixed $regions = null;

    public function render(): View
    {
        $this->regions = Region::all();

        return view('livewire.analysis.filter');
    }
}
