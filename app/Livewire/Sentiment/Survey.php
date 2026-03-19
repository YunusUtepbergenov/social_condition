<?php

namespace App\Livewire\Sentiment;

use App\Models\{Sentiment, Sentiment_Merged, Sentiment_Range, Sentiment_Republic};
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Survey extends Component
{
    protected $listeners = ['dateChanged', 'regionClicked', 'indicatorChanged'];

    public ?string $activeIndicator = null;
    public string $activeRegion = 'republic';
    public ?array $json = null;
    public ?array $ranges = null;
    public ?string $date = null;
    public ?array $top_districts = null;
    public array $dates = [];
    public array $monthlyAvg = [];
    public ?array $repAvg = null;
    public float|int|null $max = null;
    public ?array $translates = null;
    public ?string $indicators = null;

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

    public array $columns = [
        'funds' => "Жамғармага эга бўлмаган аҳоли (%)",
        'source_income' => "Доимий даромад манбаига эга бўлмаган аҳоли (%)",
        'welfare_current' => "Аҳолининг ҳозирги фаровонлиги кутилмаси индекси",
        'welfare_future' => 'Аҳолининг келгусидаги фаровинлик кутилмаси индекси',
        "inflation_current" => "Сўнгги 3 ой нарх ошганлигини билдирганлар (инфляцион сезилмалар)",
        "inflation_future" => "Келгуси 3 ойда нарх ошишини кутаётганлар (инфляцион кутилмалар)",
        "income_of_population" => "Аҳолининг ўртача даромадлари (млн. сўм)",
        "entrepreneurs_income" => "Тадбиркорларнинг ўртача даромадлари (млн. сўм)",
    ];

    public function mount(): void
    {
        $this->date = Sentiment::orderBy('date', 'DESC')->first()->date;
        $this->max = Sentiment_Merged::max('entrepreneurs_income');
        $this->dates = Sentiment_Merged::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray();
        $this->ranges = Sentiment_Range::where('date', $this->date)->get()->toArray();
        $this->translates = $this->columns;

        // Default to first indicator
        $this->activeIndicator = array_key_first($this->columns);
        $this->indicators = $this->descriptions[$this->activeIndicator];

        $mergedDate = $this->getClosestMergedDate($this->date);
        $this->top_districts = Sentiment_Merged::select(['region_code', 'region', DB::raw($this->activeIndicator . ' as value')])->where('date', $mergedDate)->orderByRaw('value DESC nulls last')->get()->toArray();
        $this->monthlyAvg = Sentiment_Republic::select('date', DB::raw($this->activeIndicator . ' as index'))->whereIn('date', $this->dates)->orderBy('date', 'ASC')->get()->pluck('index')->toArray();

        $this->makeGeoJson();
        $this->dispatch('changeMonths', dates: $this->dates);
    }

    public function render(): View
    {
        return view('livewire.sentiment.survey');
    }

    public function dateChanged(string $date): void
    {
        $this->date = $date;
        $this->activeRegion = 'republic';
        $this->dates = Sentiment_Merged::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray();
        $this->ranges = Sentiment_Range::where('date', $this->date)->get()->toArray();

        $this->indicators = $this->descriptions[$this->activeIndicator];
        $mergedDate = $this->getClosestMergedDate($date);
        $this->top_districts = Sentiment_Merged::select(['region_code', 'region', DB::raw($this->activeIndicator . ' as value')])->where('date', $mergedDate)->orderByRaw('value DESC nulls last')->get()->toArray();
        $this->monthlyAvg = Sentiment_Republic::select('date', DB::raw($this->activeIndicator . ' as index'))->whereIn('date', $this->dates)->orderBy('date', 'ASC')->get()->pluck('index')->toArray();
        $this->repAvg = null;

        $this->makeGeoJson();
        $this->dispatch('updateMap', type: 'indicator', json: $this->json, top_districts: $this->top_districts, max: $this->max, ranges: $this->ranges);
        $this->dispatch('updateChart', type: 'indicator', dates: $this->dates, data: $this->monthlyAvg, repAvg: $this->repAvg);
    }

    public function regionClicked(string $region_code): void
    {
        $this->activeRegion = $region_code;

        $mergedDate = $this->getClosestMergedDate($this->date);
        $this->top_districts = Sentiment_Merged::select(['region_code', 'region', DB::raw($this->activeIndicator . ' as value')])->where('date', $mergedDate)->orderByRaw('value DESC nulls last')->get()->toArray();
        $this->monthlyAvg = Sentiment_Merged::select('date', DB::raw('AVG(' . $this->activeIndicator . ') as average'))->where('region_code', $region_code)->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->repAvg = Sentiment_Republic::select('date', DB::raw($this->activeIndicator . ' as index'))->whereIn('date', $this->dates)->orderBy('date', 'ASC')->get()->pluck('index')->toArray();

        $this->dispatch('updateChart', type: 'indicator', dates: $this->dates, data: $this->monthlyAvg, repAvg: $this->repAvg);
    }

    public function indicatorChanged(string $indicator): void
    {
        $this->activeRegion = 'republic';
        $this->activeIndicator = $indicator;
        $this->indicators = $this->descriptions[$this->activeIndicator];

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
        $mergedDate = $this->getClosestMergedDate($this->date);
        $this->top_districts = Sentiment_Merged::select(['region_code', 'region', DB::raw($indicator . ' as value')])->where('date', $mergedDate)->orderByRaw('value DESC nulls last')->get()->toArray();
        $this->repAvg = null;
        $this->makeGeoJson();

        $this->dispatch('updateMap', type: 'indicator', json: $this->json, top_districts: $this->top_districts, max: $this->max, ranges: $this->ranges);
        $this->dispatch('updateChart', type: 'indicator', dates: $this->dates, data: $this->monthlyAvg, repAvg: $this->repAvg);
    }

    protected function getClosestMergedDate(string $date): string
    {
        return Sentiment_Merged::where('date', '<=', $date)->orderBy('date', 'DESC')->value('date') ?? $date;
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
