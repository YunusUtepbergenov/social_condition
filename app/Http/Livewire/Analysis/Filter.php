<?php

namespace App\Http\Livewire\Analysis;

use App\Models\Region;
use Livewire\Component;

class Filter extends Component
{
    public $radio = 'mood', $regions;

    public function render()
    {
        $this->regions = Region::all();
        
        return view('livewire.analysis.filter');
    }
}
