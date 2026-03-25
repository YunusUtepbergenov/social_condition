<div>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-title">
            <span class="page-icon mood"><i class="bx bx-smile"></i></span>
            <h5>Истеъмолчилар кайфияти</h5>
        </div>
        <div>
            <select class="region-select" wire:model.live="activeRegion" wire:change="regionChanged($event.target.value)">
                <option value="republic">Республика бўйича</option>
                <option value="1703">Андижон вилояти</option>
                <option value="1706">Бухоро вилояти</option>
                <option value="1708">Жиззах вилояти</option>
                <option value="1735">Қорақалроғистон Республикаси</option>
                <option value="1710">Қашқадарё вилояти</option>
                <option value="1712">Навоий вилояти</option>
                <option value="1714">Наманган вилояти</option>
                <option value="1718">Самарқанд вилояти</option>
                <option value="1722">Сурхандарё вилояти</option>
                <option value="1724">Сирдарё вилояти</option>
                <option value="1726">Тошкент шахри</option>
                <option value="1727">Тошкент вилояти</option>
                <option value="1730">Фарғона вилояти</option>
                <option value="1733">Хоразм вилояти</option>
            </select>
        </div>
    </div>

    {{-- Map + Rankings --}}
    <div class="row g-2 mb-2">
        <div class="col-sm-7" wire:ignore>
            <div class="map-panel-card">
                <div id="map" class="map-container"></div>
            </div>
        </div>
        <div class="col-sm-5">
            <div class="rankings-panel-card">
                <div class="rankings-panel-header">
                    @if ($top_districts->first()->getTable() == "bs_scores_prediction")
                        Сунъий интеллект башорати
                    @elseif($top_districts->first()->getTable() == "bs_scores")
                        Сўровнома бўйича индекс қийматлари
                    @endif
                </div>
                <div class="rankings-list top_districts">
                    @foreach($top_districts as $index => $district)
                        @component('components.district-row', [
                            'district' => $district,
                            'type' => 'mood',
                            'active_tum' => $active_tum,
                            'top_districts' => $top_districts,
                            'index' => $index
                        ])
                        @endcomponent
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Chart + Stats --}}
    <div class="row g-2 section-gap">
        <div class="col-sm-7">
            <div class="chart-panel-card">
                <div class="chart-panel-header">
                    @include('partials.chart-header', [
                        'type' => 'mood',
                        'activeIndicator' => null,
                        'activeRegion' => $activeRegion,
                        'active_tum' => $active_tum,
                        'translates' => $translates
                    ])
                </div>
                <div class="chart-panel-body" wire:ignore>
                    <canvas id="myChart1"></canvas>
                </div>
            </div>
        </div>

        @include('partials.stats-table', ['indicators' => $indicators, 'type' => 'mood', 'indicatorClass' => $indicatorClass])
    </div>

    <div wire:loading>
        <div class="loading">Loading&#8230;</div>
    </div>

    <script>
        const pageType = 'mood';
        const geoJsonUrl = "{{ asset('geojson/clean.json') }}";
        const initialOverlay = @json($this->getScoreOverlay());
        const topDistrictScore = @json($top_districts[0]['score']);
        const topDistrictScoreMin = null;
        const scoreRanges = @json($ranges);
        const dates = @json($dates);
        const monthlyAvg = @json($monthlyAvg);
        const actualAvg = @json($actualAvg);
        const activeIndicator = null;
    </script>

    @script
    <script>
        (function() {
            var s = document.createElement('script');
            s.src = "{{ asset('assets/js/map-chart.js') }}";
            document.body.appendChild(s);
        })();
    </script>
    @endscript
</div>
