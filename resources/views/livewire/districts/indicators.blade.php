<div>
    <div class="row mb-2">
        <div class="col-sm-12 d-flex align-items-center" style="gap:10px">
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
            <div style="min-width:250px" wire:ignore>
                <select class="form-select form-select-sm" id="indicator-select" style="font-size:12px">
                    @foreach ($columns as $col)
                        <option value="{{ $col }}" data-value="{{ $col }}">{{ $translates[$col] ?? $col }}</option>
                    @endforeach
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
                    @foreach($top_districts as $index => $district)
                        @component('components.district-row', [
                            'district' => $district,
                            'type' => 'indicator',
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

    <hr>

    <div class="row">
        <div class="col-sm-7">
            <div class="card" style="min-height: 15vh; max-height:28vh">
                <div class="row">
                    @include('partials.chart-header', [
                        'type' => 'indicator',
                        'activeIndicator' => $activeIndicator,
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

        @include('partials.stats-table', ['indicators' => $indicators, 'type' => 'indicator', 'indicatorClass' => $indicatorClass])
    </div>
    <hr>
    <div wire:loading>
        <div class="loading">Loading&#8230;</div>
    </div>

    <script>
        const pageType = 'indicator';
        const geoJsonData = @json($json);
        const topDistrictScore = @json($top_districts[0]['score'] ?? null);
        const topDistrictScoreMin = @json($top_districts->last()['score'] ?? null);
        const scoreRanges = null;
        const dates = @json($dates);
        const monthlyAvg = @json($monthlyAvg);
        const actualAvg = @json($actualAvg);
        const activeIndicator = @json($activeIndicator);
    </script>

    @script
    <script>
        (function() {
            $("#indicator-select").select2();

            $(document).on('change', '#indicator-select', function (e) {
                $wire.indicatorChanged($(this).find(':selected').data('value'));
            });

            var s = document.createElement('script');
            s.src = "{{ asset('assets/js/map-chart.js') }}";
            document.body.appendChild(s);
        })();
    </script>
    @endscript
</div>
