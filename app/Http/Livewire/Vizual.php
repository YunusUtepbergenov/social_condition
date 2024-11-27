<?php

namespace App\Http\Livewire;

use App\Models\{BsScore, BsScorePrediction, Cluster, ClusterDistance, DistrictCluster, Merged, MergedOrg, MutualInfo, Protest, ProtestPrediction, Range};
use App\Types\{ClusterType, IndicatorType, MoodType, ProtestType};
use Illuminate\Support\Facades\{Cache, DB, Schema};
use Livewire\Component;

class Vizual extends Component
{
    public $active_tum, $indicators, $activeIndicator, $activeRegion = 'republic';
    public $data, $json, $ranges, $clusters, $indicatorClass = 'highlightRed';
    public $date, $dates = array(), $type = 'mood', $columns;
    public $top_districts,  $monthlyAvg = array(), $actualAvg = array();

    protected $listeners = ['radioType', 'regionClicked', 'dateChanged', 'indicatorChanged', 'regionChanged'];

    public $avg_indicators = [
            "weather_temperature","weather_precipitation","weather_pollution",
            "weather_wind","weather_pressure","weather_humidity","electr_population_price",
            "electr_pop_nogas_price","electr_other_price","electr_budget_price","electr_public_utilities_price",
            "electr_industry_price","electr_сommercial_price","electr_agriculture_price","electr_transport_construction_price",
            "sug_population_price","sug_mtm_price","sug_military_price","sug_forest_price","ntl_data_ntl_mean",
            "liquified_gases_avg_price","liquified_gases_overall_price","naturalgas_agtksh_price","naturalgas_budget_price",
            "naturalgas_heat_price","naturalgas_population_price","naturalgas_sanoat_price","naturalgas_sme_price",
            "problems_narx_navo_narx_sohasida_davlat_siyosati","product_prices_price",
    ];

    public function mount(){
        $this->date = $this->getLatesDate();
        $this->dates = $this->getDates();
        $this->ranges = Cache::remember("ranges_{$this->date}", 600*600, function () {
            return Range::where('date', $this->date)->get();
        });
        $this->monthlyAvg = $this->getAverage(BsScorePrediction::class);
        $this->actualAvg = $this->getAverage(BsScore::class, 'bs_score_cur');
        $this->columns = Cache::remember("columns", 600*600, function () {
            return Schema::getColumnListing('merged_org');
        });

        $this->top_districts = $this->checkClass()->getTopDistricts($this->activeRegion, $this->activeIndicator, $this->date);

        $this->makeGeoJson();
    }

    private function getAverage($model, $column = 'score'){
        return $model::with('district')
            ->select('date', DB::raw("AVG($column) as average"))
            ->whereIn('date', $this->dates)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('average')
            ->toArray();
    }

    public function render(){
        return view('livewire.vizual');
    }

    public function openModal($feature){
        if(in_array($feature, $this->columns)){
            $tum_pop = intval(Merged::select('demography_population as population')->where('date', $this->date)->where('district_code', $this->active_tum)->first()->population);
            $population = intval(Merged::select(DB::raw('SUM(demography_population) as population'))->where('date', $this->date)->groupBy('date')->first()->population);

            if($this->type != 'indicator'){
                $date = date("Y-m-d", strtotime($this->date . "-1 month"));
            }else{
                $date = $this->date;
            }

            $data = MergedOrg::select(DB::raw($feature .' as score'), 'date')->where('district_code', $this->active_tum)->where('date', '<=', $date)->orderBy('date', 'ASC')->get()->pluck('score', 'date')->toArray();
            $dataAvg = MergedOrg::select(DB::raw($feature.'* 100000 / demography_population as score'), 'date')->where('district_code', $this->active_tum)->where('date', '<=', $date)->orderBy('date', 'ASC')->get()->pluck('score')->toArray();
            $dates = MergedOrg::select('date')->distinct()->where('date', '<', $date)->orderBy('date', 'ASC')->pluck('date')->toArray();
            $this->emit('showInfoModal', $feature, $this->active_tum, $data, $dataAvg, $date, $dates, $population, $tum_pop, $this->avg_indicators);
            $this->regionClicked($this->active_tum);
        }
    }

    public function clusterModal($feature){
        $data = ClusterDistance::select(DB::raw('value as score'), 'date')->where('indicator', $feature)->where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray();

        $this->emit('showClusterModal', $feature, $this->active_tum, $data, $this->date, $this->dates);
        $this->regionClicked($this->active_tum);
    }

    public function regionChanged($region){
        $this->emit('regionSelected', $region);
        $this->activeRegion = $region;
        $this->active_tum = null;
        $this->indicators = null;
        $class = $this->checkClass();

        $this->top_districts = $class->getTopDistricts($this->activeRegion, $this->activeIndicator, $this->date);

        if ($region !== 'republic') {
            $this->updateRegionData($class, $region);
        } else {
            $this->dateChanged($this->date);
        }

        $this->makeGeoJson();
        $this->emit('updateMap', $this->type, $this->json, $this->top_districts, $this->ranges);
    }

    private function updateRegionData($class, $region)
    {
        if (in_array($this->type, ['mood', 'protests', 'indicator'])) {
            $firstParam = $class->getRegionPredicts($region, $this->date);
            $secondParam = $class->getRegionData($region, $this->date);
            $participants = $class->getRegionParticipants($region, $this->date);
            $this->emit('updateChart', $this->dates, $firstParam, $secondParam, $participants, $this->type);
        } elseif ($this->type === 'clusters') {
            $this->updateClusterData($region);
        }
    }

    private function updateClusterData($region)
    {
        $this->calcClusters();
        $data = DistrictCluster::selectRaw('date, cluster_id, COUNT(*) as total')
            ->where('district_code', 'Like', $region.'%')
            ->groupBy('date', 'cluster_id')
            ->orderBy('date')
            ->get();

        $total = DistrictCluster::selectRaw('date, COUNT(*) as total')
            ->where('district_code', 'Like', $region.'%')
            ->groupBy('date')
            ->pluck('total', 'date');

        $percentages = $data->map(function($item) use($total) {
            $item->percentage = ($item->total / $total[$item->date]) * 100;
            return $item;
        });

        $this->emit('updateClusterChart', $this->dates, $percentages, $this->type);
    }

    public function indicatorChanged($indicator){
        if(in_array($indicator, $this->columns)){
            $this->activeIndicator = $indicator;
            if($this->active_tum){
                $this->regionClicked($this->active_tum);
            }else{
                $this->dates = $this->getDates();
                if($this->activeRegion != 'republic'){
                    $this->regionChanged($this->activeRegion);
                }else{  
                    if(in_array($indicator, $this->avg_indicators)){
                        $indicatorSum = MergedOrg::select('date',  DB::raw('AVG('.$this->activeIndicator.') as sum'))->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('sum')->toArray();
                    }else{
                        $indicatorSum = MergedOrg::select('date',  DB::raw('SUM('.$this->activeIndicator.') as sum'))->where('date', '<=', $this->date)->groupBy('date')->orderBy('date')->get()->pluck('sum')->toArray();
                    }
                    $this->top_districts = MergedOrg::with('district')->select(['district_code', 'district_name', DB::raw($indicator . ' as score')])->where('date', $this->date)->orderByRaw('score DESC nulls last')->get();
                    $this->makeGeoJson();

                    $this->emit('updateChart', $this->dates, $indicatorSum, [], [], $this->type);
                    $this->emit('updateMap', $this->type, $this->json, $this->top_districts, $this->ranges);
                }
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
        $this->dispatchBrowserEvent('componentLoaded');
    }

    public function regionClicked($tuman){
        $participants = []; $actual_avg = [];
        if($this->type != 'clusters'){
            // dd(Merged::select('demography_population as population')->where('date', $this->date)->where('district_code', $tuman)->first());
            if(isset(Merged::select('demography_population as population')->where('date', $this->date)->where('district_code', $tuman)->first()->population)){
                $population = intval(Merged::select(DB::raw('SUM(demography_population) as population'))->where('date', $this->date)->groupBy('date')->first()->population);
                $tum_pop = intval(Merged::select('demography_population as population')->where('date', $this->date)->where('district_code', $tuman)->first()->population);
            }else{
                return;
            }
        }
        // dd($tuman);

        $class = $this->checkClass();
        $this->top_districts = $class->getTopDistricts($this->activeRegion, $this->activeIndicator, $this->date);
        $this->active_tum = $tuman;
        $tum_avg = $this->getTumAvg();
        $actual_avg = $this->getTumActualAvg();

        if($this->type == 'mood'){
            $label = MoodType::getLabel($this->date, $this->active_tum);
            if($label == 1)
                $this->indicatorClass = 'highlightRed';
            else
                $this->indicatorClass = 'highlightGreen';
            $this->indicators = $class->getIndicators($tuman, $this->date, $population, $tum_pop, $this->avg_indicators);
        }
        else if($this->type == 'protests'){
            if(end($tum_avg) >= 10)
                $this->indicatorClass = 'highlightRed';
            else
                $this->indicatorClass = 'highlightGreen';
            $participants = Protest::where('district_code', $tuman)->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('participants')->toArray();
            $this->indicators = $class->getIndicators($tuman, $this->date, $population, $tum_pop, $this->avg_indicators);
        }
        else if($this->type == 'indicator'){
            $this->indicatorClass = '';
            $this->indicators = $class->getIndicators($tuman, $this->date, $population, $tum_pop, $this->avg_indicators);
        }
        else if($this->type == 'clusters'){
            $this->indicatorClass = '';
            $this->calcClusters();
            $this->indicators = ClusterDistance::where('district_code', $tuman)->where('date', $this->date)->orderBy('distance', 'ASC')->get();
        }

        $this->emit('changeTable', $tuman, $tum_avg, $actual_avg, $participants, $this->dates, $this->date, $this->type);
    }

    public function dateChanged($date){
        $this->date = $date;
        $participants = [];
        if($this->type == 'mood')
            $this->ranges = Range::where('date', $this->date)->get();
        elseif($this->type == 'clusters'){
            $this->clusters = Cluster::with('clusters')->orderBy('name', 'ASC')->get();
        }
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
                    $indicatorSum = $class->getRepublicData(in_array($this->activeIndicator, $this->avg_indicators));
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
        $path = public_path('geojson\clean.json');
        $this->json = Cache::remember('geojson_districts', 60*60*24, function () use ($path) {
            return json_decode(file_get_contents($path), true);
        });

        $districtScores = $this->top_districts->pluck('score', 'district_code')->toArray();
        $districtLabels = $this->top_districts->pluck('label', 'district_code')->toArray();
    
        foreach($this->json['features'] as &$feature){
            $districtCode = $feature['properties']['district_code'];
            if(isset($districtScores[$districtCode])){
                $feature['factors']['score'] = $districtScores[$districtCode];
                if(isset($districtLabels[$districtCode])){
                    $feature['factors']['label'] = $districtLabels[$districtCode];
                }
            }
        }
    }

    public function checkClass(){
        switch ($this->type) {
            case 'mood': return new MoodType();
            case 'protests': return new ProtestType();
            case 'indicator': return new IndicatorType($this->activeIndicator);
            case 'clusters': return new ClusterType();
        }
    }

    public function getDates(){
        if($this->type == "indicator"){
            return MergedOrg::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->pluck('date')->toArray();
        }else if($this->type == "mood"){
            return BsScorePrediction::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->pluck('date')->toArray();
            // return BsScore::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray();

        }else if($this->type == "protests"){
            return ProtestPrediction::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->pluck('date')->toArray();
        }else if($this->type == "clusters"){
            return DistrictCluster::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->pluck('date')->toArray();
        }
    }

    public function getLatesDate(){
        if($this->type == "indicator"){
            return MergedOrg::orderBy('date', 'DESC')->first()->date;
        }else if($this->type == "mood"){
            return BsScorePrediction::orderBy('date', 'DESC')->first()->date;
        }else if($this->type == "protests"){
            return ProtestPrediction::orderBy('date', 'DESC')->first()->date;
        }else if($this->type == "clusters"){
            return DistrictCluster::orderBy('date', 'DESC')->first()->date;
        }
    }

    public function getTumAvg(){
        if($this->type == "indicator"){
            $data = MergedOrg::select(DB::raw($this->activeIndicator .' as score'), 'date')->where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray();
        }else if($this->type == "mood"){
            $data = BsScorePrediction::where('district_code', $this->active_tum)->whereIn('date', $this->dates)->orderBy('date')->get()->pluck('score', 'date')->toArray();
        }else if($this->type == "protests"){
            $data = ProtestPrediction::select('prediction as score', 'date')->where('district_code', $this->active_tum)->whereDate('date', '<=', $this->date)->orderBy('date')->get()->pluck('score', 'date')->toArray();
        }else if($this->type == "clusters"){
            $data = DistrictCluster::select(DB::raw('cluster_id as score, date'))->where('district_code', $this->active_tum)->where('date', '<=', $this->date)->orderBy('date')->get()->pluck('score', 'date')->toArray();
        }
        $dates = array_fill_keys($this->dates, null);
        return ($this->type == 'clusters') ? $data : array_merge($dates, $data);
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
        $this->clusters = Cluster::with(['clusters' => function ($query) {
            $query->where('date', $this->date)
                  ->when($this->activeRegion != 'republic', function ($q) {
                      return $q->where('district_code', 'like', $this->activeRegion . '%');
                  })
                  ->orderByDesc('order');
        }])->orderBy('name', 'ASC')->get();
    
        $previousClusters = DistrictCluster::where('date', $this->date - 1)->pluck('cluster_id', 'district_code');
    
        foreach($this->clusters as $cluster){
            foreach($cluster->clusters as $data){
                if(isset($previousClusters[$data->district_code])){
                    $data->diff = $previousClusters[$data->district_code] - $data->cluster_id;
                }
            }
        }
    }

    public function calcAvg($n, $tum_pop){
        return $n * $tum_pop * 100000;
    }
}
