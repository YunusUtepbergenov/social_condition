<?php

namespace App\Livewire;

use App\Models\ProtestReason;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ReasonModal extends Component
{
    protected $listeners = ['showReasonModal'];

    public mixed $reasons = [];

    public function showReasonModal(string $date, string $activeReg, ?string $activeTum): void
    {
        $date = Carbon::parse($date);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        if ($activeTum === null) {
            if ($activeReg != 'republic') {
                $this->reasons = ProtestReason::where('district_code', 'LIKE', $activeReg . '%')->whereBetween('date', [$startOfMonth, $endOfMonth])->orderBy('date')->get();
            } else {
                $this->reasons = ProtestReason::whereBetween('date', [$startOfMonth, $endOfMonth])->orderBy('date')->get();
            }
        } else {
            $this->reasons = ProtestReason::where('district_code', $activeTum)->whereBetween('date', [$startOfMonth, $endOfMonth])->orderBy('date')->get();
        }
        $this->dispatch('openReasonModal');
    }

    public function render(): View
    {
        return view('livewire.reason-modal');
    }
}
