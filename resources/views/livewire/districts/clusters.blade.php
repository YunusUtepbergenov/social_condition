<div>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-title">
            <span class="page-icon clusters"><i class="bx bx-git-branch"></i></span>
            <h5>Ҳудудлар тоифалари</h5>
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
                <div class="rankings-panel-header">Тоифалар бўйича туманлар</div>
                <div class="rankings-list top_districts">
                    @foreach($clusters as $cluster)
                        <div class="cluster-group-header">
                            {{ $cluster->name }}
                        </div>
                        @foreach($cluster->clusters as $district)
                            @component('components.cluster-row', [
                                'district' => $district,
                                'cluster' => $cluster,
                                'active_tum' => $active_tum
                            ])
                            @endcomponent
                        @endforeach
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
                        'type' => 'clusters',
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

        @include('partials.stats-table', ['indicators' => $indicators, 'type' => 'clusters', 'indicatorClass' => $indicatorClass])
    </div>

    <div wire:loading>
        <div class="loading">Loading&#8230;</div>
    </div>

    <script>
        const pageType = 'clusters';
        const geoJsonUrl = "{{ asset('geojson/clean.json') }}";
        const initialOverlay = @json($this->getScoreOverlay());
        const topDistrictScore = @json($top_districts[0]['score'] ?? null);
        const topDistrictScoreMin = null;
        const scoreRanges = null;
        const dates = @json($dates);
        const monthlyAvg = @json($monthlyAvg);
        const actualAvg = @json($actualAvg);
        const activeIndicator = null;
        const clusterPercentages = @json($clusterPercentages);
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
