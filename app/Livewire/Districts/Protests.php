<?php

namespace App\Livewire\Districts;

use App\Abstracts\DataType;
use App\Livewire\Concerns\HasMapVisualization;
use App\Models\{Merged, Protest, ProtestPrediction};
use App\Types\ProtestType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB};
use Livewire\Component;

class Protests extends Component
{
    use HasMapVisualization;

    public ?Collection $indicators = null;

    protected $listeners = ['regionClicked', 'dateChanged', 'regionChanged', 'showChartModal'];

    public function mount(): void
    {
        $this->date = $this->getLatestDate();
        $this->dates = $this->getDates();
        $this->monthlyAvg = ProtestPrediction::select('date', DB::raw('AVG(prediction) as average'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->actualAvg = Protest::select('date', DB::raw('SUM(count) as average'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->top_districts = $this->getDataClass()->getTopDistricts($this->activeRegion, null, $this->date);
        $this->dispatch('changeMonths', dates: $this->dates);
    }

    public function render(): View
    {
        return view('livewire.districts.protests');
    }

    public function getTypeString(): string
    {
        return 'protests';
    }

    public function getDataClass(): DataType
    {
        return new ProtestType();
    }

    public function getDates(): array
    {
        return Cache::remember("dates_protest_prediction_{$this->date}", 3600, function () {
            return ProtestPrediction::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->pluck('date')->toArray();
        });
    }

    public function getLatestDate(): ?string
    {
        return Cache::remember('latest_date_protest_prediction', 3600, function () {
            return ProtestPrediction::max('date');
        });
    }

    public function getTumAvg(): array
    {
        $data = ProtestPrediction::select('prediction as score', 'date')->where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray();
        $dates = array_fill_keys($this->dates, null);
        return array_values(array_merge($dates, $data));
    }

    public function getTumActualAvg(): array
    {
        $dates = array_fill_keys($this->dates, null);
        return array_values(array_merge($dates, Protest::where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date', 'ASC')->get()->pluck('count', 'date')->toArray()));
    }

    public function showChartModal(string $date): void
    {
        $this->dispatch('showReasonModal', date: $date, activeReg: $this->activeRegion, activeTum: $this->active_tum);
        if ($this->active_tum === null) {
            $this->regionChanged($this->activeRegion);
        } else {
            $this->regionClicked($this->active_tum);
        }
    }

    public function loadDateData(): void
    {
        // No special date data for protests
    }

    public function regionClicked(string $tuman): void
    {
        $record = Merged::select('demography_population as population')->where('date', $this->date)->where('district_code', $tuman)->first();
        if ($record?->population === null) {
            return;
        }
        $population = intval(Merged::select(DB::raw('SUM(demography_population) as population'))->where('date', $this->date)->groupBy('date')->first()->population);
        $tum_pop = intval($record->population);

        $class = $this->getDataClass();
        $this->top_districts = $class->getTopDistricts($this->activeRegion, null, $this->date);
        $this->active_tum = $tuman;

        $tum_avg = $this->getTumAvg();
        $actual_avg = $this->getTumActualAvg();

        $this->indicatorClass = (end($tum_avg) >= 10) ? 'highlightRed' : 'highlightGreen';
        $participantsData = Protest::where('district_code', $tuman)->whereIn('date', $this->dates)->orderBy('date', 'ASC')->get()->pluck('participants', 'date')->toArray();
        $participantsDates = array_fill_keys($this->dates, null);
        $participants = array_values(array_merge($participantsDates, $participantsData));
        $this->indicators = $class->getIndicators($tuman, $this->date, $population, $tum_pop, $this->avg_indicators);

        $this->dispatch('changeTable', tuman: $tuman, data: $tum_avg, actual: $actual_avg, participants: $participants, dates: $this->dates, date: $this->date, type: $this->getTypeString());
    }

    public function loadRegionClickedData(string $tuman): void
    {
        // Handled in regionClicked directly
    }

    protected function loadRepublicData(): void
    {
        $monthlyAvg1 = ProtestPrediction::select('date', DB::raw('AVG(prediction) as average'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->actualAvg = Protest::select('date', DB::raw('SUM(count) as average'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $participants = Protest::select('date', DB::raw('SUM(participants) as score'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('score')->toArray();
        $this->dispatch('updateChart', dates: $this->dates, data: $monthlyAvg1, actual: $this->actualAvg, participants: $participants, type: $this->getTypeString());
    }

    protected function updateRegionData(DataType $class, string $region): void
    {
        $firstParam = $class->getRegionPredicts($region, $this->date);
        $secondParam = $class->getRegionData($region, $this->date);
        $participants = $class->getRegionParticipants($region, $this->date);
        $this->dispatch('updateChart', dates: $this->dates, data: $firstParam, actual: $secondParam, participants: $participants, type: $this->getTypeString());
    }
}
