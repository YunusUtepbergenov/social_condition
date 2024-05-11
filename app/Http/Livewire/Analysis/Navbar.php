<?php

namespace App\Http\Livewire\Analysis;

use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Navbar extends Component
{
    public $radio = 'mood', $indicators, $indicator, $region;
    public $exclude = [
                        'id','region_name', 'bs_scores_id', 'region_code', 'district_code', 'date',
                        'bs_scores_bs_gen', 'bs_scores_b_s_q2', 'bs_scores_b_s_q4', 'bs_scores_b_s_q6',
                        'bs_scores_bs_score_cur', 'bs_scores_b_s_q1', 'bs_scores_b_s_q3', 'bs_scores_b_s_q5',
                        'bs_scores_bs_score_fut', 'bs_scores_month', 'score_bs_score_cur_predict', 'district_name',
                        'ntl_data_cluster_ascending', 'ntl_data_cluster_avg', 'ntl_data_cluster_avg', 'ntl_data_cluster_max',
                        'ntl_data_cluster_min', 'ntl_data_cluster_std', 'ntl_data_cluster_std', 'ntl_data_district', 'ntl_data_region',
                        'ntl_data_region_avg', 'ntl_data_rep_avg',  'stratas_ishsizlar', 'customs_import', 'banks_deposits_balance_for_cur', 'stratas_ayollar_daftar', 'banks_not_paid_on_time_entrepreneurs',
                        'students_students', 'students_attendance', 'stratas_nogiron_shaxslar', 'students_academic_leave', 'students_dropouts', 'sug_forest_abonents',
                        'sug_forest_consump', 'sug_forest_paid', 'sug_forest_price',
                      ];

    protected $listeners = ['regionSelected'];

    public function mount(){
        $this->indicators = Schema::getColumnListing('merged_org');
        $this->indicators = array_diff($this->indicators, $this->exclude);
        // asort($this->indicators);
    $this->indicators = array_values($this->indicators);
    }

    public function render()
    {
        return view('livewire.analysis.navbar');
    }

    public function radioChanged($type){
        $this->region = 'republic';
        $this->dispatchBrowserEvent('radioChanged');
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
