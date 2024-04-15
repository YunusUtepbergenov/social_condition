<?php

namespace App\Http\Livewire\Sentiment;

use Livewire\Component;
use App\Models\Sentiment;
use App\Models\Sentiment_Question;
use Illuminate\Support\Facades\DB;

class Map extends Component
{
    protected $listeners = ['dateChanged', 'regionClicked'];

    public $vil_val, $active_tum, $indicators, $activeIndicator, $activeRegion = 'republic';
    public $data, $json, $ranges, $clusters;
    public $date;
    public $top_districts, $dates = array(), $monthlyAvg = array(), $actualAvg = array();
    public $columns;

    public function render()
    {
        return view('livewire.sentiment.map');
    }

    public function mount(){
        $this->date = $this->getLatesDate();
        $this->dates = $this->getDates();

        $this->top_districts = Sentiment::where('date', $this->date)->orderBy('value', 'DESC')->get();
        $this->monthlyAvg = Sentiment::select('date', DB::raw('AVG(value) as average'))->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->makeGeoJson();
    }

    public function dateChanged($date){
        $this->indicators = Null;
        $this->activeRegion = 'republic';
        $this->date = $date;
        $this->dates = $this->getDates();

        $this->top_districts = Sentiment::where('date', $this->date)->orderBy('value', 'DESC')->get();
        $this->monthlyAvg = Sentiment::select('date', DB::raw('AVG(value) as average'))->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->emit('updateChart', $this->dates, $this->monthlyAvg);
        $this->makeGeoJson();
        $this->emit('updateMap', $this->json, $this->top_districts);
    }

    public function regionClicked($region_code){
        $this->activeRegion = $region_code;
        $this->monthlyAvg = Sentiment::select('date', DB::raw('AVG(value) as average'))->where('region_code', $region_code)->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->indicators = Sentiment_Question::select('question', DB::raw('(very_bad + bad) as bad, normal, (good + very_good) as good'))->where('region_code', $region_code)->where('date', $this->date)->orderBy('question')->get();
        $this->emit('updateChart', $this->dates, $this->monthlyAvg);
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
        return Sentiment::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray();
    }

    public function getLatesDate(){
        return Sentiment::orderBy('date', 'DESC')->get()->pluck('date')[0];
    }
}
