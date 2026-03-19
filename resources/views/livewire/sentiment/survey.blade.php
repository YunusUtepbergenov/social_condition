<div>
    <div class="row mb-2" style="margin-top: 10px;">
        <div class="col-sm-12 d-flex align-items-center">
            <div style="min-width:280px">
                <select class="form-select form-select-sm" wire:model.live="activeIndicator" wire:change="indicatorChanged($event.target.value)" style="font-size:12px">
                    @foreach ($columns as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-8" wire:ignore>
            <div id="map" style="width: 100%; height:51vh;background:white;"></div>
        </div>
        <div class="col-sm-4">
            <div class="stats" style="width: 100%; height:51vh;background:white;overflow:auto">
                <div class="card" style="box-shadow: none;overflow-x:hidden">
                    <div class="card-body top_districts">
                        @foreach ($top_districts as $key=>$district)
                            <div class="row" style="padding: 2px 5px">
                                <div class="col-lg-6 user_name">
                                    <div class="form-check">
                                        <a href="#" id="{{$district['region_code']}}" class="form-check-label district_label" style="font-weight:{{($district['region_code'] == $activeRegion) ? 'bold': ''}};" wire:click="$dispatch('regionClicked', { region_code: '{{$district['region_code']}}' })">
                                            {{ $key + 1 }}. {{ $district['region']}}</i>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-lg-6 progress_indicator">
                                    <div class="progress">
                                        @php
                                            if ($max == 10) {
                                                $value = $district['value'] * 10;
                                            }else if($max == 100){
                                                $value = $district['value'];
                                            }else{
                                                $value = $district['value'] / $max * 100;
                                            }
                                        @endphp
                                        <div class="progress-bar"
                                            role="progressbar"
                                            style="background-color:rgb(68, 119, 170); color: #fff;width:{{ $value }}%"
                                            aria-valuemin="0"
                                            aria-valuemax="{{ $max }}">
                                            {{ number_format(round($district['value'], 1 ), 1, ',', ' ') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                    @if ($activeRegion == 'republic')
                        <div class="col-sm-12">
                            <h5 class="card-header timeline">{{ $columns[$activeIndicator] }} ( Республика бўйича )</h5>
                        </div>
                    @else
                        <div class="col-sm-12">
                            <h5 class="card-header timeline">{{ $columns[$activeIndicator] }} ( {{findRegion($activeRegion)}} бўйича )</h5>
                        </div>
                    @endif
                </div>

                <div class="card-body" style="position: relative;padding: 0 1.5rem; height: 25vh;width:100%;" wire:ignore>
                    <div class="wrapper2">
                        <canvas id="myChart1"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="stats" style="width: 100%; height:28vh;background:white;overflow:auto">
                <div class="card" style="box-shadow: none">
                    <div class="card-header">
                        <h5 style="font-weight: bold">{{ $columns[$activeIndicator] }}</h5>
                    </div>
                    <div class="card-body">
                        <p>{!! $indicators !!}</p>
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
            var sentiment_ranges = @json($ranges);
            var width = document.documentElement.clientWidth;
            if (width > 400 && width < 1500) {
                mapOptions.minZoom = 5;
                mapOptions.zoom = 5;
            }else if(width < 400){
                mapOptions.minZoom = 4;
                mapOptions.zoom = 4;
            }
            var map = L.map('map', mapOptions);
            var top_districts = @json($top_districts);
            var geojson = L.geoJSON(@json($json), {
                style: function (feature) {
                    return styleSentimentIndicatorMap(feature, top_districts[0]['value'], top_districts[top_districts.length - 1]['value']);
                },
            }).addTo(map);

            geojson.eachLayer(function (layer) {
                layer.on('click', function(e) {
                    var element = document.getElementById(this.feature.properties.region_code);
                    element.scrollIntoView({behavior: "smooth", block: "center", inline: "nearest"});
                    var $layer = e.target;
                    var highlightStyle = {
                        opacity: 1,
                        weight: 1,
                        color: 'black'
                    };
                    geojson.resetStyle();
                    $layer.bringToFront();
                    $layer.setStyle(highlightStyle);
                    Livewire.dispatch('regionClicked', { region_code: layer['feature']['properties']['region_code'] });
                });
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
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($dates),
                    datasets: [{
                        label: 'Республика бўйича',
                        data: @json($monthlyAvg),
                        borderWidth: 2,
                        borderColor: 'rgb(68, 119, 170)',
                        backgroundColor: '#bbdefb',
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

            Livewire.on('updateMap', ({ type, json, top_districts, max, ranges }) => {
                map.remove();
                map = L.map('map', mapOptions);
                geojson = L.geoJSON(json, {
                    style: function (feature) {
                        return styleSentimentIndicatorMap(feature, top_districts[0]['value'], top_districts[top_districts.length - 1]['value']);
                    },
                }).addTo(map);

                geojson.eachLayer(function (layer) {
                    layer.on('click', function(e) {
                        element = document.getElementById(this.feature.properties.region_code);
                        element.scrollIntoView({behavior: "smooth", block: "center", inline: "nearest"});

                        var $layer = e.target;
                        var highlightStyle = {
                            opacity: 1,
                            weight: 1,
                            color: 'black'
                        };
                        geojson.resetStyle();
                        $layer.bringToFront();
                        $layer.setStyle(highlightStyle);
                        Livewire.dispatch('regionClicked', { region_code: layer['feature']['properties']['region_code'] });
                    });
                });
            });

            Livewire.on('updateChart', ({ type, dates, data, repAvg }) => {
                changeIndicatorChart(data, dates, repAvg);
            });
        })();
    </script>
    @endscript
</div>
