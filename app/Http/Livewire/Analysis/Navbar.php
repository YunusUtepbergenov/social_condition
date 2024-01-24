<?php

namespace App\Http\Livewire\Analysis;

use App\Models\Merged;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Navbar extends Component
{
    public $radio = 'mood', $indicators, $indicator, $region;
    public $exclude = [
                        'id','region_name', 'bs_scores_id', 'region_code', 'district_code', 'date', 
                        'bs_scores_bs_gen', 'bs_scores_b_s_q2', 'bs_scores_b_s_q4', 'bs_scores_b_s_q6', 
                        'bs_scores_bs_score_cur', 'bs_scores_b_s_q1', 'bs_scores_b_s_q3', 'bs_scores_b_s_q5', 
                        'bs_scores_bs_score_fut', 'bs_scores_month', 'score_bs_score_cur_predict', 'district_name'
                      ];
    
    protected $listeners = ['regionSelected'];

    public function mount(){
        $this->indicators = Schema::getColumnListing('merged');
        $this->indicators = array_diff($this->indicators, $this->exclude);
        asort($this->indicators);
        $this->indicators = array_values($this->indicators);
    }
    
    public function render()
    {
        return view('livewire.analysis.navbar');
    }

    public function radioChanged($type){
        $this->radio = $type;
        $firstKey = array_key_first($this->indicators);
        $this->emit('radioType', $type, $this->indicators[$firstKey]);
        $this->emit('updateSelecttwo');
    }

    public function updatedIndicator(){
        $this->emit('indicatorChanged', $this->indicator);
    }

    public function updatedRegion(){
        $this->emit('regionChanged', $this->region);
    }

    public function regionSelected($region){
        $this->region = $region;
    }
}