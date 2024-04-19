<?php

namespace App\Http\Livewire\Sentiment;

use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Navbar extends Component
{
    public $radio = 'mood', $indicators, $indicator, $region;
    public $exclude = ['id','region', 'region_code', 'date'];
    public $columns = [
        'funds' => "Жамғармага эга бўлмаган аҳоли",
        'source_income' => "Доимий даромад манбаига эга бўлмаган аҳоли",
        'welfare_current' => "Аҳолининг ҳозирги фаровонлиги кутилмаси индекси",
        'welfare_future' => 'Аҳолининг келгусидаги фаровинлик кутилмаси индекси'
    ];
    protected $listeners = ['regionSelected'];

    public function render()
    {
        return view('livewire.sentiment.navbar');
    }
    public function mount(){
        $this->indicators = Schema::getColumnListing('pb_sentiment_merged');
        $this->indicators = array_diff($this->indicators, $this->exclude);
        asort($this->indicators);
        $this->indicators = array_values($this->indicators);
    }

    public function radioChanged($type){
        $this->radio = $type;
        $firstKey = array_key_first($this->indicators);
        $this->emit('radioType', $type, $this->indicators[$firstKey], $this->columns);
        // $this->emit('updateSelecttwo');
    }

    public function updatedIndicator(){
        $this->emit('indicatorChanged', $this->indicator);
    }

    public function updatedRegion(){
        $this->emit('regionChanged', $this->region);
    }
}
