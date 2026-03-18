<?php

namespace App\Livewire\Sentiment;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Navbar extends Component
{
    public string $radio = 'mood';
    public array $indicators = [];
    public ?string $indicator = null;
    public ?string $region = null;
    public array $exclude = ['id', 'region', 'region_code', 'date'];
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

    protected $listeners = ['regionSelected'];

    public function render(): View
    {
        return view('livewire.sentiment.navbar');
    }

    public function mount(): void
    {
        $this->indicators = Schema::getColumnListing('pb_sentiment_merged');
        $this->indicators = array_diff($this->indicators, $this->exclude);
        asort($this->indicators);
        $this->indicators = array_values($this->indicators);
    }

    public function radioChanged(string $type): void
    {
        $this->radio = $type;
        $firstKey = array_key_first($this->indicators);
        $this->dispatch('radioType', value: $type, indicator: $this->indicators[$firstKey], translates: $this->columns);
    }

    public function updatedIndicator(): void
    {
        $this->dispatch('indicatorChanged', indicator: $this->indicator);
    }

    public function updatedRegion(): void
    {
        $this->dispatch('regionChanged', region: $this->region);
    }
}
