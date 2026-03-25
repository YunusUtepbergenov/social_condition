<div>
    <br><br>
    <div class="row">
        <div class="col-sm-8" wire:ignore>
            <div id="map" style="width: 100%; height:51vh;background:white;"></div>
        </div>
        <div class="col-sm-4">
            <div class="stats" style="width: 100%; height:51vh;background:white;overflow:auto">
                <div class="card" style="box-shadow: none;overflow-x:hidden">
                    <div class="card-body top_districts">
                        @foreach ($clusters as $cluster)
                            <p>{{$cluster->name}}</p>
                            <hr>
                            @foreach ($cluster->ntl as $key=>$district)
                                @php
                                    if($district->cluster_ascending == 1)
                                        $color = 'rgb(115, 182, 107)';
                                    elseif ($district->cluster_ascending == 2)
                                        $color = 'rgb(41, 162, 198)';
                                    elseif($district->cluster_ascending == 3)
                                        $color = 'rgb(160, 160, 160)';
                                    elseif ($district->cluster_ascending == 4)
                                        $color = 'rgb(250, 167, 63)';
                                    elseif ($district->cluster_ascending == 5)
                                        $color = 'rgb(220, 85, 100)';

                                    if($district->diff > 0){
                                        $class = 'bx bxs-up-arrow-alt';
                                    }elseif ($district->diff < 0) {
                                        $class = 'bx bxs-down-arrow-alt';
                                    }else {
                                        $class = 'd-none';
                                    }
                                @endphp
                                <div class="row" style="padding: 2px 5px">
                                    <div class="col-lg-5 user_name">
                                        <div class="form-check">
                                            <a href="#" id="{{$district->district_code}}" class="form-check-label district_label" style="font-weight:{{($district->district_code == $active_tum) ? 'bold': ''}};" wire:click="$dispatch('regionClicked', { tuman: '{{$district->district_code}}' })">
                                                {{ $key + 1 }}. {{ $district->district}} <i class='{{$class}}'>{{abs($district->diff)}}</i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-lg-7 progress_indicator">
                                        <div class="progress">
                                            <div class="progress-bar"
                                                role="progressbar"
                                                style="background-color:{{$color}}; color: white;width:100%"
                                                aria-valuemin="0"
                                                aria-valuemax="100">
                                                {{$cluster->name}}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-sm-8">
            <div class="card" style="min-height: 15vh; max-height:28vh">
                <div class="row">
                    @php
                        if($type == 'mood')
                            $string = 'Истеъмолчилар кайфияти индекси (';
                        else if ($type == 'protests')
                            $string = "Оммавий норозилик бўлиш эҳтимоли (";
                        else if ($type == 'clusters')
                            $string = "Ёруғлик даражаси (";
                        else
                            $string = $translates[$activeIndicator]. ' (';
                    @endphp
                    @if ($activeRegion == 'republic')
                        @if (isset($active_tum))
                            <div class="col-sm-12">
                                <h5 class="card-header timeline">{{$string}} {{findDistrict($active_tum)}} )</h5>
                            </div>
                        @else
                            <div class="col-sm-12">
                                <h5 class="card-header timeline">{{ $string }} Республика бўйича )</h5>
                            </div>
                        @endif
                    @else
                        @if (isset($active_tum))
                            <div class="col-sm-12">
                                <h5 class="card-header timeline">{{$string}} {{findDistrict($active_tum)}} )</h5>
                            </div>
                        @else
                            <div class="col-sm-12">
                                <h5 class="card-header timeline">{{ $string.' '  }} {{findRegion($activeRegion)}} бўйича )</h5>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="card-body" style="position: relative;padding: 0 1.5rem; height: 25vh;width:100%;" wire:ignore>
                    <div class="wrapper2">
                        <canvas id="myChart1"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <hr>

    @script
    <script>
        (function() {
            var mapOptions = {
                center: [41.311, 63.2505],
                zoom: 6,
                zoomControl: false,
                minZoom: 6,
                maxZoom: 10,
                attributionControl:false
            }

            if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)){
                mapOptions.dragging = false;
            }

            var width = document.documentElement.clientWidth;
            if (width > 400 && width < 1500) {
                mapOptions.minZoom = 5;
                mapOptions.zoom = 5;
            }else if(width < 400){
                mapOptions.minZoom = 4;
                mapOptions.zoom = 4;
            }
            var map = L.map('map', mapOptions);
            var initialOverlay = @json($this->getScoreOverlay());
            var initialMaxScore = @json($top_districts[0]->cluster_ascending ?? null);
            var currentRanges = @json($ranges);
            var geoJsonUrl = "{{ asset('geojson/districts.json') }}";
            var geojson = null;
            var geoData = null;

            function applyOverlay(data, overlay) {
                data.features.forEach(function(f) {
                    var code = f.properties.district_code;
                    if (overlay[code] !== undefined) {
                        if (!f.factors) f.factors = {};
                        f.factors.score = overlay[code];
                    }
                });
                return data;
            }

            function bindLayerClicks() {
                geojson.eachLayer(function (layer) {
                    layer.on('click', function(e) {
                        var element = document.getElementById(this.feature.properties.district_code);
                        if (element) element.scrollIntoView({behavior: "smooth", block: "center", inline: "nearest"});
                        var $layer = e.target;
                        geojson.resetStyle();
                        $layer.bringToFront();
                        $layer.setStyle({ opacity: 1, weight: 1, color: 'black' });
                        Livewire.dispatch('regionClicked', { tuman: layer['feature']['properties']['district_code'] });
                    });
                });
            }

            fetch(geoJsonUrl).then(r => r.json()).then(function(data) {
                geoData = data;
                applyOverlay(geoData, initialOverlay);
                geojson = L.geoJSON(geoData, {
                    style: function (feature) {
                        return styleCluster(feature, initialMaxScore, currentRanges);
                    },
                }).addTo(map);
                bindLayerClicks();
            });

            window.addEventListener('resize', function() {
                var width = document.documentElement.clientWidth;
                if (width > 400 && width < 1500) {
                    map.setZoom(5);
                }else {
                    map.setZoom(6);
                }
                map.invalidateSize();
            });

            const ctx = document.getElementById('myChart1');
            var chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($dates),
                    datasets: [{
                        label: 'Ёруғлик даражаси',
                        data: @json($monthlyAvg),
                        borderWidth: 3,
                        borderColor: 'rgb(232, 9, 9)',
                        backgroundColor: 'rgb(232, 9, 9)',
                        yAxisID: 'y',
                    }],
                },
                options: {
                    plugins: {
                        legend: {
                            display: true
                        },
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 1,
                    scales: {
                        y: {
                            beginAtZero: false,
                            position: 'left',
                            ticks: {
                               stepSize: 0.25
                            },
                        },
                    }
                },
            });

            Livewire.on('changeTable', ({ tuman, data, actual, participants, dates, date, type }) => {
                var string = '';
                switch (type) {
                    case 'mood':
                        string = 'Аҳоли кайфияти ';
                        changeTableContentsandChart(data, actual, dates, type, string);
                        break;
                    case 'protests':
                        string = 'Оммавий норозилик содир бўлиши эҳтимоли ';
                        changeProtestChart(data, actual, dates, type, string, participants);
                        break;
                    case 'indicator':
                        string = @json($activeIndicator);
                        changeIndicatorChart(data, dates);
                        break;
                    case 'clusters':
                        changeClusterChart2(data, dates);
                        break;
                }
            });

            Livewire.on('updateMap', ({ type, overlay, top_districts, ranges }) => {
                if (!geoData || !geojson) return;
                currentRanges = ranges;
                applyOverlay(geoData, overlay);
                geojson.clearLayers();
                geojson.addData(geoData);

                var maxScore = top_districts && top_districts[0] ? top_districts[0]['score'] : null;
                if(type == 'mood'){
                    geojson.setStyle(function(feature) { return style1(feature, maxScore, currentRanges); });
                }else if(type == 'protests'){
                    geojson.setStyle(function(feature) { return styleProtestMap(feature, maxScore); });
                }else if(type == 'indicator'){
                    geojson.setStyle(function(feature) { return styleIndicator(feature, maxScore); });
                }else if(type == 'clusters'){
                    geojson.setStyle(function(feature) { return styleCluster(feature); });
                }
                bindLayerClicks();
            });

            Livewire.on('updateChart', ({ dates, data, actual, participants, type }) => {
                var string = '';
                switch (type) {
                    case 'mood':
                        string = 'Аҳоли кайфияти ';
                        changeTableContentsandChart(data, actual, dates, type, string);
                        break;
                    case 'protests':
                        string = 'Оммавий норозилик содир бўлиши эҳтимоли ';
                        changeProtestChart(data, actual, dates, type, string, participants);
                        break;
                    case 'indicator':
                        string = 'Индикатор ';
                        changeIndicatorChart(data, dates);
                        break;
                }
            });

            Livewire.on('updateClusterChart', ({ dates, percentages, type }) => {
                changeClusterChart(dates, percentages, type);
            });
        })();
    </script>
    @endscript
</div>
