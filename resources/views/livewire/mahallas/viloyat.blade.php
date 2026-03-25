<div>

    <div class="row h-100">
        <div class="col-sm-7">
            <div id="uzbekistan-map" class="map-container" style="height: 100%; width: 100%;"></div>
        </div>

        <div class="col-sm-5 d-flex flex-column p-3" style="background: #f8f9fa;">
            <div class="stats" style="width: 100%; height:45vh;background:white;overflow:auto">
                <div class="card" style="box-shadow: none;overflow-x:hidden">
                    <div class="card-body top_districts">
                        <div class="row" style="padding: 2px 5px">
                            <div class="col-lg-4 user_name">
                                <div class="form-check" style="font-weight: bold">
                                    Ҳудудлар
                                </div>
                            </div>
                            <div class="col-lg-8 progress_indicator">
                                <div class="progress">
                                    <div class="progress-bar cluster-1" role="progressbar" style="width: 20%" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100">1-тоифа</div>
                                    <div class="progress-bar cluster-2" role="progressbar" style="width: 20%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100">2-тоифа</div>
                                    <div class="progress-bar cluster-3" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">3-тоифа</div>
                                    <div class="progress-bar cluster-4" role="progressbar" style="width: 20%" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100">4-тоифа</div>
                                    <div class="progress-bar cluster-5" role="progressbar" style="width: 20%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100">5-тоифа</div>
                                </div>
                            </div>
                            <br>
                            <br>
                            @foreach($result as $key=>$region)
                                <div class="col-lg-4 user_name mt-2">
                                    <div class="form-check">
                                        <a class="form-check-label district_label">{{ $key + 1 }}. {{ $region['region_code'] }}</a>
                                    </div>
                                </div>
                                <div class="col-lg-8 progress_indicator mt-2">
                                    <div class="progress" style="height: 22px;">
                                        @php
                                            $clusters = [
                                                ['count' => $region['cluster1'], 'color' => 'cluster-1'],
                                                ['count' => $region['cluster2'], 'color' => 'cluster-2'],
                                                ['count' => $region['cluster3'], 'color' => 'cluster-3'],
                                                ['count' => $region['cluster4'], 'color' => 'cluster-4'],
                                                ['count' => $region['cluster5'], 'color' => 'cluster-5'],
                                            ];
                                        @endphp

                                        @foreach($clusters as $index => $cluster)
                                            @php
                                                $percentage = round(100 * $cluster['count'] / $region['total_mahallas'], 1);
                                                $width = max($percentage, 10);
                                            @endphp
                                            <div class="progress-bar {{ $cluster['color'] }}"
                                                role="progressbar"
                                                style="width: {{ $width }}%; position: relative;"
                                                aria-valuenow="{{ $percentage }}"
                                                aria-valuemin="0"
                                                aria-valuemax="100">{{ $percentage }}%
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="indicators-table mt-3" style="height:38vh; overflow-y: auto;">
                <h5 class="mb-3 text-center">Индикаторлар таҳлили</h5>
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Индикатор</th>
                            <th>Республика кўрсаткичи</th>
                            <th>Тоифа кўрсаткичи</th>
                            <th>Туман кўрсаткичи</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- @foreach($indicators as $index => $indicator)
                            <tr>
                                <td>{{ $index+1 }}</td>
                                <td>{{ $indicator['name'] }}</td>
                                <td>{{ $indicator['republic'] }}</td>
                                <td>{{ $indicator['cluster'] }}</td>
                                <td>{{ $indicator['district'] }}</td>
                            </tr>
                        @endforeach --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        @media (min-width: 1400px) {
            .progress {
                font-size: 11px;
                font-weight: 500;
            }
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function () {

            var mapOptions = {
                center: [41.311, 64.2505],
                zoom: 6,
                zoomControl: false,
                minZoom: 6,
                maxZoom: 10,
                attributionControl: false,
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
            var map = L.map('uzbekistan-map', mapOptions);
            var geojson = null;
            var initialOverlay = @json($this->getRegionOverlay());
            var geoJsonUrl = "{{ asset('geojson/regional.json') }}";

            function applyRegionOverlay(data, overlay) {
                data.features.forEach(function(f) {
                    var code = f.properties.region_code || f.properties.code;
                    if (overlay[code] !== undefined) {
                        if (!f.factors) f.factors = {};
                        f.factors.cluster = overlay[code];
                    }
                });
                return data;
            }

            function bindLayerClicks(codeProperty) {
                geojson.eachLayer(function (layer) {
                    layer.on('click', function(e) {
                        Livewire.dispatch('regionClicked', { region_code: layer['feature']['properties'][codeProperty] });
                    });
                });
            }

            fetch(geoJsonUrl).then(r => r.json()).then(function(data) {
                applyRegionOverlay(data, initialOverlay);
                geojson = L.geoJSON(data, {
                    style: function (feature) {
                        return styleRegion(feature);
                    },
                }).addTo(map);
                bindLayerClicks('region_code');
            });

            Livewire.on('updateMap', ({ overlay, geoJsonUrl }) => {
                fetch(geoJsonUrl).then(r => r.json()).then(function(data) {
                    applyRegionOverlay(data, overlay);
                    if (geojson) {
                        geojson.clearLayers();
                        geojson.addData(data);
                        geojson.setStyle(function(feature) { return styleRegion(feature); });
                    } else {
                        geojson = L.geoJSON(data, {
                            style: function (feature) { return styleRegion(feature); },
                        }).addTo(map);
                    }
                    bindLayerClicks('code');
                    map.fitBounds(geojson.getBounds(), { animate: true, padding: [10, 10] });
                });
            });
        });
    </script>
</div>
