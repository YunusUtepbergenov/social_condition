<div>
<div class="row">
    <div class="col-sm-7" wire:ignore>
        <div id="map" class="map-container"></div>
    </div>
    <div class="col-sm-5 stats-container">
        <div class="card card-fixed">
            <div class="card-header text-center">
                @if ($top_districts->first()->getTable() == "bs_scores_prediction")
                    <h5 style="text-align: center; padding:5px; margin:0;">Сунъий интеллект башорати</h5>
                    <hr>
                @elseif($top_districts->first()->getTable() == "bs_scores")
                    <h5 style="text-align: center; padding:5px; margin:0;">Сўровнома бўйича индекс қийматлари</h5>
                    <hr>
                @endif
            </div>
            <div class="card-body top_districts">
                @if($type !== 'clusters')
                    @foreach($top_districts as $index => $district)
                        @component('components.district-row', [
                            'district' => $district,
                            'type' => $type,
                            'active_tum' => $active_tum,
                            'top_districts' => $top_districts,
                            'index' => $index
                        ])
                        @endcomponent
                    @endforeach
                @else
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
                @endif
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
                    'type' => $type,
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

    {{-- Statistics table (leave mostly as is) --}}
    @include('partials.stats-table', ['indicators' => $indicators, 'type' => $type, 'indicatorClass' => $indicatorClass])
</div>
    <hr>
    <div wire:loading>
        <div class="loading">Loading&#8230;</div>
    </div>

    <script>
        const geoJsonData = @json($json);
        const topDistrictScore = @json($top_districts[0]['score']);
        const scoreRanges = @json($ranges);
        const dates = @json($dates);
        const monthlyAvg = @json($monthlyAvg);
        const actualAvg = @json($actualAvg);
        const activeIndicator = @json($activeIndicator);


    </script>

    @prepend('scripts')
        <script src="{{asset('assets/js/map-chart.js')}}"></script>
    @endprepend
</div>
