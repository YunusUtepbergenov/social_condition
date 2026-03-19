<div>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-title">
            <span class="page-icon sentiment-survey"><i class="bx bx-poll"></i></span>
            <h5>Сўровнома натижалари</h5>
        </div>
        <div>
            <select class="region-select" wire:model.live="activeIndicator" wire:change="indicatorChanged($event.target.value)" style="min-width:280px">
                @foreach ($columns as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Map + Rankings --}}
    <div class="row g-2 mb-2">
        <div class="col-sm-8" wire:ignore>
            <div class="map-panel-card">
                <div id="map" class="map-container"></div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="rankings-panel-card">
                <div class="rankings-panel-header">Вилоятлар рейтинги</div>
                <div class="rankings-list top_districts">
                    @foreach ($top_districts as $key => $district)
                        <div class="district-row">
                            <span class="rank-num">{{ $key + 1 }}</span>
                            <a href="#" id="{{ $district['region_code'] }}"
                               class="district_label"
                               style="font-weight:{{ ($district['region_code'] == $activeRegion) ? '700' : '500' }};"
                               wire:click="$dispatch('regionClicked', { region_code: '{{ $district['region_code'] }}' })">
                                {{ $district['region'] }}
                            </a>
                            <div style="width:90px;flex-shrink:0;">
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
                                        style="background-color:rgb(68, 119, 170); color: #fff; width:{{ $value }}%"
                                        aria-valuemin="0"
                                        aria-valuemax="{{ $max }}">
                                        {{ number_format(round($district['value'], 1), 1, ',', ' ') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Chart + Indicator Description --}}
    <div class="row g-2 section-gap">
        <div class="col-sm-8">
            <div class="chart-panel-card">
                <div class="chart-panel-header">
                    @if ($activeRegion == 'republic')
                        <h6>{{ $columns[$activeIndicator] }} ( Республика бўйича )</h6>
                    @else
                        <h6>{{ $columns[$activeIndicator] }} ( {{ findRegion($activeRegion) }} бўйича )</h6>
                    @endif
                </div>
                <div class="chart-panel-body" wire:ignore>
                    <div class="wrapper2">
                        <canvas id="myChart1"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="sentiment-indicator-card">
                <div class="card-header">
                    <h5>{{ $columns[$activeIndicator] }}</h5>
                </div>
                <div class="card-body">
                    <p>{!! $indicators !!}</p>
                </div>
            </div>
        </div>
    </div>

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
