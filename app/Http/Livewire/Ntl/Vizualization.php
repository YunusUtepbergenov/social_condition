<?php

namespace App\Http\Livewire\Ntl;

use App\Models\Cluster;
use App\Models\NtlData;
use App\Models\Range;
use App\Types\Ntl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Vizualization extends Component
{
    public $vil_val, $active_tum, $indicators, $activeIndicator, $activeRegion = 'republic';
    public $data, $json, $ranges, $clusters;
    public $date, $type;
    public $top_districts, $dates = array(), $monthlyAvg = array(), $actualAvg = array();
    public $columns;

    public function mount(){
        $this->clusters = Cluster::with('clusters')->orderBy('name', 'ASC')->get();
        $this->ranges = Range::where('date', $this->date)->get();

        $this->type = 'clusters';
        $this->date = $this->getLatesDate();
        $this->dates = $this->getDates();

        $this->top_districts = Ntl::getTopDistricts($this->activeRegion, null, $this->date);
        $this->monthlyAvg = NtlData::with('district')->select('date', DB::raw('AVG(ntl_mean) as average'))->groupBy('date')->orderBy('date')->get()->pluck('average')->toArray();
        $this->calcClusters();
        $this->makeGeoJson();
    }

    public function render()
    {
        return view('livewire.ntl.vizualization');
    }

    public function makeGeoJson(){
        $path = public_path('geojson\districts.json');
        $this->json = json_decode(file_get_contents($path), true);

        foreach($this->top_districts as $district){
            foreach($this->json['features'] as $key=>$feature){
                if($district->district_code == $feature['properties']['district_code']){
                    $this->json['features'][$key]['factors']['score'] = $district->cluster_ascending;
                    if(isset($district->label)){
                        $this->json['features'][$key]['factors']['label'] = $district->label;
                    }
                    break;
                }
            }
        }
    }

    public function getDates(){
        return NtlData::select('date')->distinct('date')->where('date', '<=', $this->date)->orderBy('date', 'ASC')->get()->pluck('date')->toArray();
    }

    public function getLatesDate(){
        return NtlData::orderBy('date', 'DESC')->get()->pluck('date')[0];
    }

    public function calcClusters(){
        $this->clusters = Cluster::with('clusters')->orderBy('name', 'ASC')->get();
        if($this->activeRegion == 'republic'){
            foreach($this->clusters as $cluster){
                $cluster->ntl = $cluster->ntl->where('date', $this->date);
                $cluster->ntl = $cluster->ntl->sortByDesc('order')->values()->all();
            }
        }else{
            foreach($this->ntl as $cluster){
                $cluster->ntl = $cluster->clusters->where('date', $this->date)
                                    ->filter(function (NtlData $value){
                                        return strpos($value->district_code, $this->activeRegion) === 0;
                                    })->sortByDesc('order')
                                    ->values();
            }
        }
    }
}
