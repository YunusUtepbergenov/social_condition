<?php

namespace App\Livewire\Districts;

use App\Abstracts\DataType;
use App\Livewire\Concerns\HasMapVisualization;
use App\Models\{BsScore, BsScorePrediction, Merged};
use App\Types\MoodType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Mood extends Component
{
    use HasMapVisualization;

    public ?Collection $indicators = null;
    public mixed $ranges = null;

    protected $listeners = ['regionClicked', 'dateChanged', 'regionChanged'];

    public function mount(): void
    {
        $this->date = $this->getLatestDate();
        $this->dates = $this->getDates();
        $this->ranges = MoodType::getRanges($this->date);
        $this->monthlyAvg = $this->getAverage(BsScorePrediction::class);
        $this->actualAvg = $this->getAverage(BsScore::class, 'bs_score_cur');
        $this->top_districts = $this->getDataClass()->getTopDistricts($this->activeRegion, null, $this->date);
        $this->makeGeoJson();
        $this->dispatch('changeMonths', dates: $this->dates);
    }

    public function render(): View
    {
        return view('livewire.districts.mood');
    }

    public function getTypeString(): string
    {
        return 'mood';
    }

    public function getDataClass(): DataType
    {
        return new MoodType();
    }

    public function getDates(): array
    {
        return BsScorePrediction::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->pluck('date')->toArray();
    }

    public function getLatestDate(): ?string
    {
        return BsScorePrediction::orderBy('date', 'DESC')->first()?->date;
    }

    public function getTumAvg(): array
    {
        $data = BsScorePrediction::where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray();
        $dates = array_fill_keys($this->dates, null);
        return array_merge($dates, $data);
    }

    public function getTumActualAvg(): array
    {
        $dates = array_fill_keys($this->dates, null);
        return array_merge($dates, BsScore::where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('bs_score_cur', 'date')->toArray());
    }

    public function loadDateData(): void
    {
        $this->ranges = MoodType::getRanges($this->date);
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

        $label = $class->getLabel($this->date, $this->active_tum);
        $this->indicatorClass = ($label == 1) ? 'highlightRed' : 'highlightGreen';
        $this->indicators = $class->getIndicators($tuman, $this->date, $population, $tum_pop, $this->avg_indicators);

        $this->dispatch('changeTable', tuman: $tuman, data: $tum_avg, actual: $actual_avg, participants: [], dates: $this->dates, date: $this->date, type: $this->getTypeString());
    }

    public function loadRegionClickedData(string $tuman): void
    {
        // Handled in regionClicked directly
    }

    protected function loadRepublicData(): void
    {
        $monthlyAvg1 = BsScorePrediction::select('date', DB::raw('AVG(score) as average'))->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->actualAvg = BsScore::select('date', DB::raw('AVG(bs_score_cur) as average'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->dispatch('updateChart', dates: $this->dates, data: $monthlyAvg1, actual: $this->actualAvg, participants: [], type: $this->getTypeString());
    }

    protected function updateRegionData(DataType $class, string $region): void
    {
        $firstParam = $class->getRegionPredicts($region, $this->date);
        $secondParam = $class->getRegionData($region, $this->date);
        $this->dispatch('updateChart', dates: $this->dates, data: $firstParam, actual: $secondParam, participants: [], type: $this->getTypeString());
    }

    private function getAverage(string $model, string $column = 'score'): array
    {
        return $model::with('district')
            ->select('date', DB::raw("AVG($column) as average"))
            ->whereIn('date', $this->dates)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('average')
            ->toArray();
    }
}
