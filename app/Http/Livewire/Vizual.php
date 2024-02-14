<?php

namespace App\Http\Livewire;

use App\Models\BsScore;
use App\Models\BsScorePrediction;
use App\Models\Cluster;
use App\Models\ClusterDistance;
use App\Models\DistrictCluster;
use App\Models\Merged;
use App\Models\MergedOrg;
use App\Models\Protest;
use App\Models\ProtestPrediction;
use App\Models\Range;
use App\Types\ClusterType;
use App\Types\IndicatorType;
use App\Types\MoodType;
use App\Types\ProtestType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Vizual extends Component
{
    public $vil_val, $active_tum, $indicators, $activeIndicator, $activeRegion = 'republic';
    public $data, $json, $ranges, $clusters;
    public $date;
    public $top_districts, $dates = array(), $monthlyAvg = array(), $actualAvg = array();
    public $type = 'mood', $sum;

    protected $listeners = ['radioType', 'regionClicked', 'dateChanged', 'indicatorChanged', 'regionChanged'];
    
    public $avg_indicators = [
                'weather_temperature', 'weather_precipitation', 'weather_pollution', 
                'weather_wind', 'weather_pressure', 'weather_humidity',
                'electr_population_price', 'electr_pop_nogas_price', 'electr_other_price',
                'electr_budget_price', 'electr_public_utilities_price', 'electr_industry_price',
                'electr_сommercial_price', 'electr_agriculture_price', 'electr_transport_construction_price',
                'sug_population_price', 'sug_mtm_price', 'sug_military_price', 'sug_forest_price'
    ];

    public function mount(){
        $this->clusters = Cluster::with('clusters')->orderBy('name', 'ASC')->get();
        $this->date = $this->getLatesDate();
        $this->dates = $this->getDates();
        $this->ranges = Range::where('date', $this->date)->get();
        $this->monthlyAvg = BsScorePrediction::with('district')->select('date', DB::raw('AVG(score) as average'))->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->actualAvg = BsScore::with('district')->select('date', DB::raw('AVG(bs_score_cur) as average'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        
        $this->top_districts = $this->checkClass()->getTopDistricts($this->activeRegion, $this->activeIndicator, $this->date);
        $this->makeGeoJson();
    }

    public function render(){
        return view('livewire.vizual');
    }

    public function openModal($feature){
        $tum_pop = intval(Merged::select('demography_population as population')->where('date', $this->date)->where('district_code', $this->active_tum)->first()->population);
        $population = intval(Merged::select(DB::raw('SUM(demography_population) as population'))->where('date', $this->date)->groupBy('date')->first()->population);
        
        $data = Merged::select(DB::raw($feature .' as score'), 'date')->where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray();
        $dataAvg = Merged::select(DB::raw($feature .' / '. $tum_pop. '*'. 100000 .' as score'), 'date')->where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score')->toArray();

        $this->emit('showInfoModal', $feature, $this->active_tum, $data, $dataAvg ,$this->date, $this->dates, $population, $tum_pop);
        $this->regionClicked($this->active_tum);
    }

    public function regionChanged($region){
        $this->emit('regionSelected', $region);
        $this->activeRegion = $region;
        $this->active_tum = null;
        $this->indicators = null;
        $class = $this->checkClass();

        $this->top_districts = $class->getTopDistricts($this->activeRegion, $this->activeIndicator, $this->date);

        if($region == 'republic'){
            $this->dateChanged($this->date);
        }else{
            
            if($this->type == 'mood' || $this->type == 'protests' || $this->type == 'indicator'){
                $firstParam = $class->getRegionPredicts($region, $this->date);
                $secondParam = $class->getRegionData($region, $this->date);
                $participants = $class->getRegionParticipants($region, $this->date);
                
                $this->emit('updateChart', $this->dates, $firstParam, $secondParam, $participants, $this->type);   
            }
            else if ($this->type == 'clusters'){
                $this->calcClusters();
                $data = DistrictCluster::select(['date', 'cluster_id', DB::raw('COUNT(*) as total')])->where('district_code', 'Like', $this->activeRegion.'%')->groupBy('date', 'cluster_id')->orderBy('date', 'ASC')->get();
                $total = DistrictCluster::select(['date', DB::raw('COUNT(*) as total')])->where('district_code', 'Like', $this->activeRegion.'%')->groupBy('date')->get();
                $percentages = $data->map(function($item) use($total){
                    $totalForMonth = $total->firstWhere('date', $item->date)->total;
                    $item->percentage = ($item->total / $totalForMonth) * 100;
                    return $item;
                });
                
                $this->emit('updateClusterChart', $this->dates, $percentages, $this->type);
            }
        }

        $this->makeGeoJson();
        $this->emit('updateMap', $this->type, $this->json, $this->top_districts, $this->ranges);
    }

    public function indicatorChanged($indicator){
        $this->activeIndicator = $indicator;
        if($this->active_tum){
            $this->regionClicked($this->active_tum);
        }else{
            $this->dates = $this->getDates();
            if($this->activeRegion != 'republic'){
                $this->regionChanged($this->activeRegion);
            }else{
                $indicatorSum = MergedOrg::select('date',  DB::raw('SUM('.$this->activeIndicator.') as sum'))->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('sum')->toArray();
                $this->top_districts = MergedOrg::with('district')->select(['district_code', 'district_name', DB::raw($indicator . ' as score')])->where('date', $this->date)->orderByRaw('score DESC nulls last')->get();
                $this->makeGeoJson();
                $this->emit('updateChart', $this->dates, $indicatorSum, [], [], $this->type);
                $this->emit('updateMap', $this->type, $this->json, $this->top_districts, $this->ranges);                
            }
        }
    }

    public function radioType($value, $indicator){
        $this->type = $value;
        $this->active_tum = null;
        $this->indicators = null;
        $this->activeRegion = 'republic';
        $this->activeIndicator = $indicator;
        $this->date = $this->getLatesDate();
        $this->dates = $this->getDates();
        $this->dateChanged($this->date);
        $this->emit('changeMonths', $this->dates);
        $this->makeGeoJson();
        $this->emit('regionSelected', $this->activeRegion);
    }

    public function regionClicked($tuman){
        $participants = []; $actual_avg = [];
        $population = intval(Merged::select(DB::raw('SUM(demography_population) as population'))->where('date', $this->date)->groupBy('date')->first()->population);
        $tum_pop = intval(Merged::select('demography_population as population')->where('date', $this->date)->where('district_code', $tuman)->first()->population);
        
        $class = $this->checkClass();
        $this->top_districts = $class->getTopDistricts($this->activeRegion, $this->activeIndicator, $this->date);
        $this->active_tum = $tuman;

        $tum_avg = $this->getTumAvg();
        $actual_avg = $this->getTumActualAvg();

        if($this->type == 'mood'){
            $this->indicators = $class->getIndicators($tuman, $this->date, $population, $tum_pop, $this->avg_indicators);
        }
        else if($this->type == 'protests'){
            $participants = Protest::where('district_code', $tuman)->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('participants')->toArray();
            $this->indicators = $class->getIndicators($tuman, $this->date, $population, $tum_pop, $this->avg_indicators);
        }
        else if($this->type == 'clusters'){
            $this->calcClusters();
            $this->indicators = ClusterDistance::where('district_code', $tuman)->where('date', $this->date)->orderBy('distance', 'ASC')->get();
        }

        $this->emit('changeTable', $tuman, $tum_avg, $actual_avg, $participants, $this->dates, $this->date, $this->type);
    }

    public function dateChanged($date){
        $this->date = $date;
        $participants = [];
        $this->dates = $this->getDates();

        $class = $this->checkClass();

        if($this->active_tum){
            $this->regionClicked($this->active_tum);
            $this->makeGeoJson();
            $this->emit('updateMap', $this->type, $this->json, $this->top_districts, $this->ranges);
        }else{
            if($this->activeRegion != 'republic'){
                $this->regionChanged($this->activeRegion);
            }else{
                $this->top_districts = $class->getTopDistricts($this->activeRegion, $this->activeIndicator, $this->date);
                if($this->type == "indicator"){
                    $indicatorSum = $class->getRepublicData();
                    $this->emit('updateChart', $this->dates, $indicatorSum, [], [], $this->type);
                }
                else if($this->type == "mood"){
                    $monthlyAvg1 = BsScorePrediction::select('date', DB::raw('AVG(score) as average'))->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
                    $this->actualAvg = BsScore::select('date', DB::raw('AVG(bs_score_cur) as average'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();    
                    $this->emit('updateChart', $this->dates, $monthlyAvg1, $this->actualAvg, $participants, $this->type);
                }
                else if($this->type == 'protests'){
                    $monthlyAvg1 = ProtestPrediction::select('date', DB::raw('AVG(prediction) as average'))->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
                    $this->actualAvg = Protest::select('date', DB::raw('SUM(count) as average'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
                    $participants = Protest::select('date', DB::raw('SUM(participants) as score'))->whereIn('date', $this->dates)->groupBy('date')->orderBy('date')->get()->pluck('score')->toArray();
    
                    $this->emit('updateChart', $this->dates, $monthlyAvg1, $this->actualAvg, $participants, $this->type);         
                }
                else if($this->type == "clusters"){
                    $this->calcClusters();

                    $data = DistrictCluster::select(['date', 'cluster_id', DB::raw('COUNT(*) as total')])->groupBy('date', 'cluster_id')->orderBy('date', 'ASC')->get();
                    $total = DistrictCluster::select(['date', DB::raw('COUNT(*) as total')])->groupBy('date')->get();
                    $percentages = $data->map(function($item) use($total){
                        $totalForMonth = $total->firstWhere('date', $item->date)->total;
                        $item->percentage = ($item->total / $totalForMonth) * 100;
                        return $item;
                    });
                    $this->emit('updateClusterChart', $this->dates, $percentages, $this->type);
                }
                $this->makeGeoJson();
                $this->emit('updateMap', $this->type, $this->json, $this->top_districts, $this->ranges);
            }
        }
    }

    // ------------------------ HELPER FUNCTIONS ------------------------

    public function makeGeoJson(){
        $path = public_path('geojson\districts.json');
        $this->json = json_decode(file_get_contents($path), true);

        foreach($this->top_districts as $district){
            foreach($this->json['features'] as $key=>$feature){
                if($district->district_code == $feature['properties']['district_code']){
                    $this->json['features'][$key]['factors']['score'] = $district->score;
                    if(isset($district->label)){
                        $this->json['features'][$key]['factors']['label'] = $district->label;
                    }
                    break;
                }
            }
        }
    }

    public function checkClass(){
        switch ($this->type) {
            case 'mood':
                return new MoodType();
                break;
            case 'protests':
                return new ProtestType();
                break;
            case 'indicator':
                return new IndicatorType($this->activeIndicator);
                break;
            case 'clusters':
                return new ClusterType();
                break;
        }
    }

    public function getDates(){
        if($this->type == "indicator"){
            return MergedOrg::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray();
        }else if($this->type == "mood"){
            return BsScorePrediction::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray();
        }else if($this->type == "protests"){
            return ProtestPrediction::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray();
        }else if($this->type == "clusters"){
            return DistrictCluster::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray();
        }
    }

    public function getLatesDate(){
        if($this->type == "indicator"){
            return MergedOrg::orderBy('date', 'DESC')->get()->pluck('date')[0];
        }else if($this->type == "mood"){
            return BsScorePrediction::orderBy('date', 'DESC')->get()->pluck('date')[0];
        }else if($this->type == "protests"){
            return ProtestPrediction::orderBy('date', 'DESC')->get()->pluck('date')[0];
        }else if($this->type == "clusters"){
            return DistrictCluster::orderBy('date', 'DESC')->get()->pluck('date')[0];
        }
    }

    public function getTumAvg(){
        $dates = array_fill_keys($this->dates, null);
        if($this->type == "indicator"){
            $data = MergedOrg::select(DB::raw($this->activeIndicator .' as score'), 'date')->where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray(); 
        }else if($this->type == "mood"){
            $data = BsScorePrediction::where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray();
        }else if($this->type == "protests"){
            $data = ProtestPrediction::select('prediction as score', 'date')->where('district_code', $this->active_tum)->whereDate('date', '<=', $this->date)->orderBy('date')->get()->pluck('score', 'date')->toArray();
        }else if($this->type == "clusters"){
            $data = DistrictCluster::select(DB::raw('cluster_id as score, date'))->where('district_code', $this->active_tum)->whereDate('date', '<=', $this->date)->orderBy('date')->get()->pluck('score', 'date')->toArray();
        }
        return array_merge($dates, $data);
    }

    public function getTumActualAvg(){
        $dates = array_fill_keys($this->dates, null);
        if($this->type == "mood"){
            return array_merge($dates, BsScore::where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('bs_score_cur', 'date')->toArray());
        }else if($this->type == "protests"){
            return array_values(array_merge($dates, Protest::where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date', 'ASC')->get()->pluck('count', 'date')->toArray()));
        }else {
            return []; 
        }
    }

    public function calcClusters(){
        $this->clusters = Cluster::with('clusters')->orderBy('name', 'ASC')->get();
        if($this->activeRegion == 'republic'){
            foreach($this->clusters as $cluster){
                $cluster->clusters = $cluster->clusters->where('date', $this->date);
                $cluster->clusters = $cluster->clusters->sortByDesc('order')->values()->all();
                foreach($cluster->clusters as $data){
                    $val = DistrictCluster::where([
                        'district_code' => $data->district_code,
                        'date' => Carbon::parse($data->date)->subYear(1),
                    ])->first();
                    if(isset($val)){
                        $data->diff =  $val->cluster_id - $data->cluster_id;    
                    }
                }
            }
        }else{
            foreach($this->clusters as $cluster){
                $cluster->clusters = $cluster->clusters->where('date', $this->date)
                                    ->filter(function (DistrictCluster $value){
                                        return strpos($value->district_code, $this->activeRegion) === 0;
                                    })->sortByDesc('order')
                                    ->values();

                foreach($cluster->clusters as $data){
                    $val = DistrictCluster::where([
                        'district_code' => $data->district_code,
                        'date' => Carbon::parse($data->date)->subYear(1),
                    ])->first();
                    if(isset($val)){
                        $data->diff =  $val->cluster_id - $data->cluster_id;    
                    }
                }
            }
        }
    }
}
