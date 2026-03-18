<?php

namespace App\Livewire\Sentiment;

use Livewire\Component;
use App\Models\Sentiment;
use App\Models\Sentiment_Merged;
use App\Models\Sentiment_Question;
use App\Models\Sentiment_Range;
use App\Models\Sentiment_Republic;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class Map extends Component
{
    protected $listeners = ['dateChanged', 'regionClicked', 'radioType', 'indicatorChanged'];

    public mixed $vil_val = null;
    public ?string $active_tum = null;
    public mixed $indicators = null;
    public mixed $prev_indicators = null;
    public ?string $activeIndicator = null;
    public string $activeRegion = 'republic';
    public mixed $data = null;
    public ?array $json = null;
    public ?string $type = null;
    public mixed $repAvg = null;
    public float|int|null $max = null;
    public ?array $translates = null;
    public mixed $ranges = null;
    public ?string $date = null;
    public mixed $top_districts = null;
    public array $dates = [];
    public array $monthlyAvg = [];
    public array $actualAvg = [];

    public array $descriptions = [
        'funds' => "<b>Ўзгариш оралиғи (%):</b> 0 дан 100 гача. <br>0 – респондентлар орасида жамғармага эга бўлмаганлари <b><u>мавжуд эмас</u></b>, яъни респондентларнинг барчаси жамғармага эга;<br>100 – респондентларнинг барчаси жамғармага эга эмас.",
        'source_income' => "<b>Ўзгариш оралиғи (%):</b> 0 дан 100 гача. <br>0 – респондентлар орасида доимий даромад манбаига эга бўлмаганлари <b><u>мавжуд эмас</u></b>, яъни респондентларнинг барчаси доимий даромад манбаига эга;<br>100 – респондентларнинг барчаси доимий даромад манбаига эга эмас.",
        'welfare_current' => "Респондентларнинг <i>ҳозирги фаровонлиги даражаси</i> (0 дан 10 гача бутун сонлар, 0-энг қуйи ва 10-энг юқори) ҳақидаги саволга белгилаган жафоблари ўртачаси олинади.<br><b>Ўзгариш оралиғи (%):</b> 0 дан 10 гача. <br>0 – респондентларнинг барчаси ҳозирги фаровонлиги даражасини 0 деб белгилаган;<br> 10 – респондентларнинг барчаси ҳозирги фаровонлиги даражасини 10 деб белгилаган.",
        'welfare_future' => 'Респондентларнинг <i>келгусидаги фаровонлиги даражаси</i> (0 дан 10 гача бутун сонлар, 0-энг қуйи ва 10-энг юқори) ҳақидаги саволга белгилаган жафоблари ўртачаси олинади.<br><b>Ўзгариш оралиғи (%):</b> 0 дан 10 гача. <br>0 – респондентларнинг барчаси келгусидаги фаровонлиги даражасини 0 деб белгилаган;<br>10 – респондентларнинг барчаси келгусидаги фаровонлиги даражасини 10 деб белгилаган.',
        "inflation_current" => "Сўнгги 3 ой нарх ошганлигини билдирганлар (инфляцион сезилмалар)",
        "inflation_future" => "Келгуси 3 ойда нарх ошишини кутаётганлар (инфляцион кутилмалар)",
        "income_of_population" => "Аҳолининг ўртача даромадлари (млн. сўм)",
        "entrepreneurs_income" => "Тадбиркорларнинг ўртача даромадлари (млн. сўм)",
    ];

    public function render(): View
    {
        return view('livewire.sentiment.map');
    }

    public function mount(): void
    {
        $this->type = 'mood';
        $this->max = Sentiment_Merged::max('entrepreneurs_income');
        $this->date = $this->getLatesDate();
        $this->ranges = Sentiment_Range::where('date', $this->date)->get();
        $this->dates = $this->getDates();
        $this->activeIndicator = null;
        $this->top_districts = Sentiment::where('date', $this->date)->orderBy('value', 'DESC')->get();
        $this->monthlyAvg = Sentiment_Republic::select('date', DB::raw('sentiment_index as index'))->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('index')->toArray();
        $prev_month = date("Y-m-d", strtotime($this->date . "-1 month"));

        $this->indicators = Sentiment_Question::select('question', DB::raw('(very_bad + bad) as bad, normal, (good + very_good) as good'))->where('region_code', 1700)->where('date', $this->date)->orderBy('question')->get();
        $this->prev_indicators = Sentiment_Question::select('question', DB::raw('(very_bad + bad) as bad, normal, (good + very_good) as good'))->where('region_code', 1700)->where('date', $prev_month)->orderBy('question')->get();

        $this->makeGeoJson();
    }

    public function dateChanged(string $date): void
    {
        $this->date = $date;
        $prev_month = date("Y-m-d", strtotime($date . "-1 month"));

        $this->indicators = Sentiment_Question::select('question', DB::raw('(very_bad + bad) as bad, normal, (good + very_good) as good'))->where('region_code', 1700)->where('date', $this->date)->orderBy('question')->get();
        $this->prev_indicators = Sentiment_Question::select('question', DB::raw('(very_bad + bad) as bad, normal, (good + very_good) as good'))->where('region_code', 1700)->where('date', $prev_month)->orderBy('question')->get();

        $this->activeRegion = 'republic';

        $this->dates = $this->getDates();
        $this->ranges = Sentiment_Range::where('date', $this->date)->get();

        if ($this->type == 'mood') {
            $this->top_districts = Sentiment::where('date', $this->date)->orderBy('value', 'DESC')->get();
            $this->repAvg = null;
            $this->monthlyAvg = Sentiment_Republic::select('date', DB::raw('sentiment_index as index'))->whereIn('date', $this->dates)->get()->pluck('index')->toArray();
        } elseif ($this->type == 'indicator') {
            $this->showIndicatorDescription();
            $this->top_districts = Sentiment_Merged::select(['region_code', 'region', DB::raw($this->activeIndicator . ' as value')])
                ->where('date', $date)
                ->orderByRaw('value DESC nulls last')
                ->get();
            $this->monthlyAvg = Sentiment_Republic::select('date', DB::raw($this->activeIndicator . ' as index'))->whereIn('date', $this->dates)->orderBy('date', 'ASC')->get()->pluck('index')->toArray();
            $this->repAvg = null;
        }

        $this->makeGeoJson();
        $this->dispatch('updateMap', type: $this->type, json: $this->json, top_districts: $this->top_districts, max: $this->max, ranges: $this->ranges);
        $this->dispatch('updateChart', type: $this->type, dates: $this->dates, data: $this->monthlyAvg, repAvg: $this->repAvg);
    }

    public function radioType(string $value, string $indicator, array $translates): void
    {
        $this->type = $value;
        $this->translates = $translates;
        $this->active_tum = null;
        $this->indicators = null;
        $this->activeIndicator = $indicator;
        $this->date = $this->getLatesDate();
        $this->dates = $this->getDates();

        $this->dateChanged($this->date);
        $this->dispatch('changeMonths', dates: $this->dates);
        $this->makeGeoJson();
    }

    public function regionClicked(string $region_code): void
    {
        $prev_month = date("Y-m-d", strtotime($this->date . "-1 month"));
        $this->activeRegion = $region_code;

        if ($this->type == 'mood') {
            $this->monthlyAvg = Sentiment::select('date', DB::raw('AVG(value) as average'))->where('region_code', $region_code)->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
            $this->indicators = Sentiment_Question::select('question', DB::raw('(very_bad + bad) as bad, normal, (good + very_good) as good'))->where('region_code', $region_code)->where('date', $this->date)->orderBy('question')->get();
            $this->prev_indicators = Sentiment_Question::select('question', DB::raw('(very_bad + bad) as bad, normal, (good + very_good) as good'))->where('region_code', $region_code)->where('date', $prev_month)->orderBy('question')->get();
            $this->repAvg = Sentiment_Republic::select('date', DB::raw('sentiment_index as index'))->whereIn('date', $this->dates)->orderBy('date', 'ASC')->get()->pluck('index')->toArray();
        } else {
            $this->top_districts = Sentiment_Merged::select(['region_code', 'region', DB::raw($this->activeIndicator . ' as value')])->where('date', $this->date)->orderByRaw('value DESC nulls last')->get();
            $this->monthlyAvg = Sentiment_Merged::select('date', DB::raw('AVG(' . $this->activeIndicator . ') as average'))->where('region_code', $region_code)->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
            $this->repAvg = Sentiment_Republic::select('date', DB::raw($this->activeIndicator . ' as index'))->whereIn('date', $this->dates)->orderBy('date', 'ASC')->get()->pluck('index')->toArray();
        }
        $this->dispatch('updateChart', type: $this->type, dates: $this->dates, data: $this->monthlyAvg, repAvg: $this->repAvg);
    }

    public function indicatorChanged(string $indicator): void
    {
        $this->activeRegion = 'republic';
        $this->activeIndicator = $indicator;
        $this->showIndicatorDescription();
        if (in_array($indicator, ['welfare_current', 'welfare_future'])) {
            $this->max = 10;
        } elseif ($indicator == 'income_of_population') {
            $this->max = Sentiment_Merged::max('income_of_population');
        } elseif ($indicator == 'entrepreneurs_income') {
            $this->max = Sentiment_Merged::max('entrepreneurs_income');
        } else {
            $this->max = 100;
        }

        $this->monthlyAvg = Sentiment_Republic::select('date', DB::raw($this->activeIndicator . ' as index'))->whereIn('date', $this->dates)->orderBy('date', 'ASC')->get()->pluck('index')->toArray();
        $this->top_districts = Sentiment_Merged::select(['region_code', 'region', DB::raw($indicator . ' as value')])->where('date', $this->date)->orderByRaw('value DESC nulls last')->get();
        $this->repAvg = null;
        $this->makeGeoJson();

        $this->dispatch('updateMap', type: $this->type, json: $this->json, top_districts: $this->top_districts, max: $this->max, ranges: $this->ranges);
        $this->dispatch('updateChart', type: $this->type, dates: $this->dates, data: $this->monthlyAvg, repAvg: $this->repAvg);
    }

    public function showIndicatorDescription(): void
    {
        $this->indicators = $this->descriptions[$this->activeIndicator];
    }

    public function makeGeoJson(): void
    {
        $path = public_path('geojson/regional.json');
        $this->json = json_decode(file_get_contents($path), true);

        foreach ($this->top_districts as $district) {
            foreach ($this->json['features'] as $key => $feature) {
                if ($district->region_code == $feature['properties']['region_code']) {
                    $this->json['features'][$key]['factors']['score'] = $district->value;
                    $this->json['features'][$key]['factors']['label'] = $district->label;
                    break;
                }
            }
        }
    }

    public function getDates(): array
    {
        return match ($this->type) {
            'mood' => Sentiment::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray(),
            default => Sentiment_Merged::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray(),
        };
    }

    public function getLatesDate(): string
    {
        return Sentiment::orderBy('date', 'DESC')->first()->date;
    }
}
