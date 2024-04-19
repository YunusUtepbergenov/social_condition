<?php

namespace App\Http\Livewire\Sentiment;

use Livewire\Component;
use App\Models\Sentiment;
use App\Models\Sentiment_Merged;
use App\Models\Sentiment_Question;
use Illuminate\Support\Facades\DB;

class Map extends Component
{
    protected $listeners = ['dateChanged', 'regionClicked', 'radioType', 'indicatorChanged'];

    public $vil_val, $active_tum, $indicators, $prev_indicators, $activeIndicator, $activeRegion = 'republic';
    public $data, $json, $type, $max, $translates;
    public $date;
    public $top_districts, $dates = array(), $monthlyAvg = array(), $actualAvg = array();

    public function render()
    {
        return view('livewire.sentiment.map');
    }

    public function mount(){
        $this->type = 'mood';
        $this->max = 100;
        $this->date = $this->getLatesDate();
        $this->dates = $this->getDates();
        $this->activeIndicator = Null;
        $this->top_districts = Sentiment::where('date', $this->date)->orderBy('value', 'DESC')->get();
        $this->monthlyAvg = Sentiment::select('date', DB::raw('AVG(value) as average'))->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->makeGeoJson();
    }

    public function dateChanged($date){
        $this->indicators = Null;
        $this->activeRegion = 'republic';
        $this->date = $date;
        $this->dates = $this->getDates();

        if($this->type == 'mood'){
            $this->top_districts = Sentiment::where('date', $this->date)->orderBy('value', 'DESC')->get();
            $this->monthlyAvg = Sentiment::select('date', DB::raw('AVG(value) as average'))->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        }elseif($this->type == 'indicator'){
            $this->top_districts = Sentiment_Merged::select(['region_code', 'region', DB::raw($this->activeIndicator . ' as value')])
                                    ->where('date', $date)
                                    ->orderByRaw('value DESC nulls last')
                                    ->get();
            $this->monthlyAvg = Sentiment_Merged::select('date',  DB::raw('AVG('.$this->activeIndicator.') as average'))->groupBy('date')->where('date', '<=', $this->date)->orderBy('date')->get()->pluck('average')->toArray();
        }

        $this->makeGeoJson();
        $this->emit('updateMap', $this->type, $this->json, $this->top_districts, $this->max);
        $this->emit('updateChart', $this->type, $this->dates, $this->monthlyAvg, $this->activeIndicator);
    }

    public function radioType($value, $indicator, $translates){
        $this->type = $value;
        $this->translates = $translates;
        $this->active_tum = null;
        $this->indicators = null;
        $this->activeIndicator = $indicator;
        $this->date = $this->getLatesDate();
        $this->dates = $this->getDates();
        $this->dateChanged($this->date);
        $this->emit('changeMonths', $this->dates);
        $this->makeGeoJson();
        // $this->emit('regionSelected', $this->activeRegion);
    }

    public function regionClicked($region_code){
        $prev_month = date("Y-m-d", strtotime($this->date . "-1 month"));
        $this->activeRegion = $region_code;

        if($this->type == 'mood'){
            $this->monthlyAvg = Sentiment::select('date', DB::raw('AVG(value) as average'))->where('region_code', $region_code)->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
            $this->indicators = Sentiment_Question::select('question', DB::raw('(very_bad + bad) as bad, normal, (good + very_good) as good'))->where('region_code', $region_code)->where('date', $this->date)->orderBy('question')->get();
            $this->prev_indicators = Sentiment_Question::select('question', DB::raw('(very_bad + bad) as bad, normal, (good + very_good) as good'))->where('region_code', $region_code)->where('date', $prev_month)->orderBy('question')->get();
        }else{
            $this->top_districts = Sentiment_Merged::select(['region_code', 'region', DB::raw($this->activeIndicator . ' as value')])->where('date', $this->date)->orderByRaw('value DESC nulls last')->get();
            $this->monthlyAvg = Sentiment_Merged::select('date',  DB::raw('AVG('.$this->activeIndicator.') as average'))->where('region_code', $region_code)->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        }
        $this->emit('updateChart', $this->type, $this->dates, $this->monthlyAvg, $this->activeIndicator);
    }

    public function indicatorChanged($indicator){
        $this->activeIndicator = $indicator;
        if(in_array($indicator, ['welfare_current', 'welfare_future'])){
            $this->max = 10;
        }else{
            $this->max = 100;
        }
        $this->monthlyAvg = Sentiment_Merged::select('date',  DB::raw('AVG('.$this->activeIndicator.') as average'))->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->top_districts = Sentiment_Merged::select(['region_code', 'region', DB::raw($indicator . ' as value')])->where('date', $this->date)->orderByRaw('value DESC nulls last')->get();
        $this->makeGeoJson();

        $this->emit('updateMap', $this->type, $this->json, $this->top_districts, $this->max);
        $this->emit('updateChart', $this->type, $this->dates, $this->monthlyAvg, $this->activeIndicator);
    }

    public function makeGeoJson(){
        $path = public_path('geojson\regional.json');
        $this->json = json_decode(file_get_contents($path), true);

        foreach($this->top_districts as $district){
            foreach($this->json['features'] as $key=>$feature){
                if($district->region_code == $feature['properties']['region_code']){
                    $this->json['features'][$key]['factors']['score'] = $district->value;
                    $this->json['features'][$key]['factors']['color'] = $district->color;
                    break;
                }
            }
        }
    }

    public function getDates(){
        if($this->type == 'mood')
            return Sentiment::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray();
        else
            return Sentiment_Merged::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray();
    }

    public function getLatesDate(){
        return Sentiment::orderBy('date', 'DESC')->get()->pluck('date')[0];
    }
}
