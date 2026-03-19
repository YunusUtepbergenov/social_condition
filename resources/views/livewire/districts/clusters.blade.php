<div>
    <div class="row mb-2">
        <div class="col-sm-12 d-flex align-items-center">
            <div style="min-width:200px">
                <select class="form-select form-select-sm" wire:model.live="activeRegion" wire:change="regionChanged($event.target.value)" style="font-size:12px">
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
    </div>

    <div class="row">
        <div class="col-sm-7" wire:ignore>
            <div id="map" class="map-container"></div>
        </div>
        <div class="col-sm-5 stats-container">
            <div class="card card-fixed">
                <div class="card-body top_districts">
                    @foreach($clusters as $cluster)
                        <p>{{ $cluster->name }}</p>
                        <hr>
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

    <hr>

    <div class="row">
        <div class="col-sm-7">
            <div class="card" style="min-height: 15vh; max-height:28vh">
                <div class="row">
                    @include('partials.chart-header', [
                        'type' => 'clusters',
                        'activeIndicator' => null,
                        'activeRegion' => $activeRegion,
                        'active_tum' => $active_tum,
                        'translates' => $translates
                    ])
                </div>
                <div class="card-body p-0 px-3" style="height: 25vh;" wire:ignore>
                    <canvas id="myChart1"></canvas>
                </div>
            </div>
        </div>

        @include('partials.stats-table', ['indicators' => $indicators, 'type' => 'clusters', 'indicatorClass' => $indicatorClass])
    </div>
    <hr>
    <div wire:loading>
        <div class="loading">Loading&#8230;</div>
    </div>

    <script>
        const pageType = 'clusters';
        const geoJsonData = @json($json);
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
