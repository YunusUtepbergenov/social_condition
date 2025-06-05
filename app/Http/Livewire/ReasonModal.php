<?php

namespace App\Http\Livewire;

use App\Models\ProtestReason;
use Carbon\Carbon;
use Livewire\Component;

class ReasonModal extends Component
{
    protected $listeners = ['showReasonModal'];

    public $reasons = [];

    public function showReasonModal($date, $activeReg, $activeTum){
        $date = Carbon::parse($date);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        if($activeTum == Null){
            if($activeReg != 'republic')
                $this->reasons = ProtestReason::where('district_code', 'LIKE', $activeReg.'%')->whereBetween('date', [$startOfMonth, $endOfMonth])->orderBy('date')->get();
            else
                $this->reasons = ProtestReason::whereBetween('date', [$startOfMonth, $endOfMonth])->orderBy('date')->get();
        }else{
            $this->reasons = ProtestReason::where('district_code', $activeTum)->whereBetween('date', [$startOfMonth, $endOfMonth])->orderBy('date')->get();
        }
        $this->dispatchBrowserEvent('openReasonModal');
    }

    public function render()
    {
        return view('livewire.reason-modal');
    }
}
