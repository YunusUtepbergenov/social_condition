<div>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-title">
            <span class="page-icon sentiment-mood"><i class="bx bx-user-voice"></i></span>
            <h5>Аҳоли кайфияти индекси</h5>
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
                                    <div class="progress-bar"
                                        role="progressbar"
                                        style="background-color:rgb(68, 119, 170); color: #fff; width:{{ ($district['value'] / 10) * 100 }}%"
                                        aria-valuemin="0"
                                        aria-valuemax="10">
                                        {{ $district['value'] }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Chart + Stats --}}
    <div class="row g-2 section-gap">
        <div class="col-sm-8">
            <div class="chart-panel-card">
                <div class="chart-panel-header">
                    @if ($activeRegion == 'republic')
                        <h6>Аҳоли кайфияти индекси ( Республика бўйича )</h6>
                    @else
                        <h6>Аҳоли кайфияти индекси ( {{ findRegion($activeRegion) }} бўйича )</h6>
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
            <div class="stats-panel-card">
                <table class="table" id="district_stat">
                    <thead class="thead-light" id="thead">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Саволлар</th>
                            <th scope="col">Салбий (ўзгариш, %)</th>
                            <th scope="col">Нейтрал (ўзгариш, %)</th>
                            <th scope="col">Ижобий (ўзгариш, %)</th>
                        </tr>
                    </thead>
                    <tbody id="indikatorlar">
                        @isset($indicators)
                            @foreach ($indicators as $key => $indicator)
                                @isset($prev_indicators[$key])
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td><a href="#">{{ $indicator['question'] }}</a></td>
                                        <td>{{ number_format(round(($indicator['bad'] - $prev_indicators[$key]['bad']) * 100, 1), 1, ',', ' ') }}</td>
                                        <td>{{ number_format(round(($indicator['normal'] - $prev_indicators[$key]['normal']) * 100, 1), 1, ',', ' ') }}</td>
                                        <td>{{ number_format(round(($indicator['good'] - $prev_indicators[$key]['good']) * 100, 1), 1, ',', ' ') }}</td>
                                    </tr>
                                @endisset
                            @endforeach
                        @endisset
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @script
    <script>
        (function() {
            var mapOptions = {
                center: [41.311, 63.2505],
                zoom: 5,
                zoomControl: false,
                minZoom: 5,
                maxZoom: 10,
                zoomSnap: 0.25,
                zoomDelta: 0.25,
                attributionControl:false
            }

            if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)){
                mapOptions.dragging = false;
            }
            var sentiment_ranges = @json($ranges);
            var map = L.map('map', mapOptions);
            var geojson = L.geoJSON(@json($json), {
                style: function (feature) {
                    return styleSentimentMap(feature, sentiment_ranges);
                },
            }).addTo(map);

            requestAnimationFrame(function() {
                map.invalidateSize();
                map.fitBounds(geojson.getBounds(), { animate: false, padding: [10, 10] });
            });

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
                map.invalidateSize();
                map.fitBounds(geojson.getBounds(), { animate: false, padding: [10, 10] });
            });

            const ctx = document.getElementById('myChart1');
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($dates),
                    datasets: [{
                        label: 'Республика бўйича',
                        data: @json($monthlyAvg),
                        borderColor: chartColors.primary,
                        pointBackgroundColor: chartColors.primary,
                        fill: true,
                        yAxisID: 'y',
                    }],
                },
                options: {
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + formatChartNumber(context.parsed.y);
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false,
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
                        return styleSentimentMap(feature, ranges);
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

                requestAnimationFrame(function() {
                    map.invalidateSize();
                    map.fitBounds(geojson.getBounds(), { animate: false, padding: [10, 10] });
                });
            });

            Livewire.on('updateChart', ({ type, dates, data, repAvg }) => {
                changeSentimentChart(data, dates, repAvg);
            });
        })();
    </script>
    @endscript
</div>
