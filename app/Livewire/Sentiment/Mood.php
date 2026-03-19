<?php

namespace App\Livewire\Sentiment;

use App\Models\{Sentiment, Sentiment_Question, Sentiment_Range, Sentiment_Republic};
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Mood extends Component
{
    protected $listeners = ['dateChanged', 'regionClicked'];

    public ?array $indicators = null;
    public ?array $prev_indicators = null;
    public string $activeRegion = 'republic';
    public ?array $json = null;
    public ?array $ranges = null;
    public ?string $date = null;
    public ?array $top_districts = null;
    public array $dates = [];
    public array $monthlyAvg = [];
    public ?array $repAvg = null;

    public function mount(): void
    {
        $this->date = Sentiment::orderBy('date', 'DESC')->first()->date;
        $this->dates = Sentiment::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray();
        $this->ranges = Sentiment_Range::where('date', $this->date)->get()->toArray();
        $this->top_districts = Sentiment::where('date', $this->date)->orderBy('value', 'DESC')->get()->toArray();
        $this->monthlyAvg = Sentiment_Republic::select('date', DB::raw('sentiment_index as index'))->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('index')->toArray();

        $prev_month = date("Y-m-d", strtotime($this->date . "-1 month"));
        $this->indicators = Sentiment_Question::select('question', DB::raw('(very_bad + bad) as bad, normal, (good + very_good) as good'))->where('region_code', 1700)->where('date', $this->date)->orderBy('question')->get()->toArray();
        $this->prev_indicators = Sentiment_Question::select('question', DB::raw('(very_bad + bad) as bad, normal, (good + very_good) as good'))->where('region_code', 1700)->where('date', $prev_month)->orderBy('question')->get()->toArray();

        $this->makeGeoJson();
        $this->dispatch('changeMonths', dates: $this->dates);
    }

    public function render(): View
    {
        return view('livewire.sentiment.mood');
    }

    public function dateChanged(string $date): void
    {
        $this->date = $date;
        $prev_month = date("Y-m-d", strtotime($date . "-1 month"));

        $this->indicators = Sentiment_Question::select('question', DB::raw('(very_bad + bad) as bad, normal, (good + very_good) as good'))->where('region_code', 1700)->where('date', $this->date)->orderBy('question')->get();
        $this->prev_indicators = Sentiment_Question::select('question', DB::raw('(very_bad + bad) as bad, normal, (good + very_good) as good'))->where('region_code', 1700)->where('date', $prev_month)->orderBy('question')->get();

        $this->activeRegion = 'republic';
        $this->dates = Sentiment::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray();
        $this->ranges = Sentiment_Range::where('date', $this->date)->get()->toArray();
        $this->top_districts = Sentiment::where('date', $this->date)->orderBy('value', 'DESC')->get()->toArray();
        $this->repAvg = null;
        $this->monthlyAvg = Sentiment_Republic::select('date', DB::raw('sentiment_index as index'))->whereIn('date', $this->dates)->get()->pluck('index')->toArray();

        $this->makeGeoJson();
        $this->dispatch('updateMap', type: 'mood', json: $this->json, top_districts: $this->top_districts, max: null, ranges: $this->ranges);
        $this->dispatch('updateChart', type: 'mood', dates: $this->dates, data: $this->monthlyAvg, repAvg: $this->repAvg);
    }

    public function regionClicked(string $region_code): void
    {
        $prev_month = date("Y-m-d", strtotime($this->date . "-1 month"));
        $this->activeRegion = $region_code;

        $this->monthlyAvg = Sentiment::select('date', DB::raw('AVG(value) as average'))->where('region_code', $region_code)->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->indicators = Sentiment_Question::select('question', DB::raw('(very_bad + bad) as bad, normal, (good + very_good) as good'))->where('region_code', $region_code)->where('date', $this->date)->orderBy('question')->get()->toArray();
        $this->prev_indicators = Sentiment_Question::select('question', DB::raw('(very_bad + bad) as bad, normal, (good + very_good) as good'))->where('region_code', $region_code)->where('date', $prev_month)->orderBy('question')->get()->toArray();
        $this->repAvg = Sentiment_Republic::select('date', DB::raw('sentiment_index as index'))->whereIn('date', $this->dates)->orderBy('date', 'ASC')->get()->pluck('index')->toArray();

        $this->dispatch('updateChart', type: 'mood', dates: $this->dates, data: $this->monthlyAvg, repAvg: $this->repAvg);
    }

    public function makeGeoJson(): void
    {
        $path = public_path('geojson/regional.json');
        $this->json = json_decode(file_get_contents($path), true);

        foreach ($this->top_districts as $district) {
            foreach ($this->json['features'] as $key => $feature) {
                if ($district['region_code'] == $feature['properties']['region_code']) {
                    $this->json['features'][$key]['factors']['score'] = $district['value'];
                    $this->json['features'][$key]['factors']['label'] = $district['label'] ?? null;
                    break;
                }
            }
        }
    }
}
