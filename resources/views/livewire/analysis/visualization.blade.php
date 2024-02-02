<div>
    <div class="row">
        <div class="col-sm-8" wire:ignore>
            <div id="map" style="width: 100%; height:51vh;background:white;"></div>
        </div>
        <div class="col-sm-4">
            <div class="stats" style="width: 100%; height:51vh;background:white;overflow:auto">
                <div class="card" style="box-shadow: none;overflow-x:hidden">
                    <div class="card-body top_districts">
                        @if($type != 'clusters')
                            @foreach ($top_districts as $key => $district)
                                <div class="row" style="padding: 2px 5px">
                                    <div class="{{($type == 'clusters') ? 'col-lg-5' : 'col-lg-5'}} user_name">
                                        @if ($type == 'mood')
                                            @php
                                                if($district->label == 3)
                                                    $color = 'rgb(4, 117, 53)';
                                                elseif ($district->label == 2)
                                                    $color = 'rgb(115, 115, 115)';
                                                else
                                                    $color = '#be4343';
                                            @endphp
                                        @endif
                                        <div class="form-check">
                                            @if ($type == 'indicator')
                                                <a class="form-check-label district_label" id="{{$district->district_code}}" style="font-weight:{{($district->district_code == $active_tum) ? 'bold': ''}};" wire:click="$emit('regionClicked', '{{$district->district_code}}')">
                                                    {{ $key + 1 }}. {{ $district->district_name}}
                                                </a>
                                            @elseif ($type == 'clusters')
                                                <a href="#" id="{{$district->district_code}}" class="form-check-label district_label" style="font-weight:{{($district->district_code == $active_tum) ? 'bold': ''}};" wire:click="$emit('regionClicked', '{{$district->district_code}}')"> {{ $key + 1 }}. {{ $district->district->name}}</a>
                                            @else
                                                <a href="#" class="form-check-label district_label" id="{{$district->district_code}}" style="font-weight:{{($district->district_code == $active_tum) ? 'bold': ''}};" wire:click="$emit('regionClicked', '{{$district->district_code}}')">
                                                    {{ $key + 1 }}. {{ $district->district->name}}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-lg-7 progress_indicator">
                                        <div class="progress">
                                            @if ($type == 'mood')
                                                <div class="progress-bar" role="progressbar" style="transition-duration: 600ms; background-color:{{$color}}; color: white;width:{{( $district->score / 200 ) * 100 }}%" aria-valuemin="0" aria-valuemax="100">{{number_format( round($district->score, 1), 1, ',', ' ' ) }}</div>
                                            @elseif ($type == 'protests')
                                                <div class="progress-bar" role="progressbar" style="transition-duration: 600ms; color: white;width:{{($district->score > 9) ? $district->score: '9' }}%" aria-valuemin="0" aria-valuemax="100">{{ number_format( $district->score, 1, ',', ' ' )}}</div>
                                            @elseif($type == 'indicator')
                                                @if ($top_districts->first()->score)
                                                    <div class="progress-bar" role="progressbar" 
                                                        style="transition-duration: 600ms;background-color:rgb(68, 119, 170);color: white;width:{{(($district->score / $top_districts->first()->score)*100 > 15) ? ($district->score / $top_districts->first()->score)*100 : '15' }}%" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100">
                                                        {{ number_format( round($district->score, 1), 1, ',', ' ' )}}
                                                    </div>                                              
                                                @else
                                                    <div class="progress-bar" role="progressbar" 
                                                        style="transition-duration: 600ms;background-color:rgb(68, 119, 170);color: white;width:10%" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100">
                                                        {{ number_format( round($district->score, 1), 1, ',', ' ' )}}
                                                    </div>                                                                                        
                                                @endif
                                                @elseif ($type == 'clusters')
                                                    <div class="progress-bar" 
                                                        role="progressbar" 
                                                        style="background-color:{{$color}}; color: white;width:100%" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100">
                                                        {{$district->score}}-кластер
                                                    </div>
                                            @endif
                                        </div>
                                    </div>                                    
                                </div>
                            @endforeach
                        @else
                            @foreach ($clusters as $cluster)
                                <p>{{$cluster->name}}</p>
                                <hr>
                                @foreach ($cluster->clusters as $key=>$district)
                                    @php
                                        if($district->cluster_id == 1)
                                            $color = 'rgb(115, 182, 107)';
                                        elseif ($district->cluster_id == 2)
                                            $color = 'rgb(41, 162, 198)';
                                        elseif($district->cluster_id == 3)
                                            $color = 'rgb(160, 160, 160)';
                                        elseif ($district->cluster_id == 4)
                                            $color = 'rgb(250, 167, 63)';
                                        elseif ($district->cluster_id == 5)
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
                                        <div class="{{($type == 'clusters') ? 'col-lg-5' : 'col-lg-5'}} user_name">                                                                    
                                            <div class="form-check">
                                                <a href="#" id="{{$district->district_code}}" class="form-check-label district_label" style="font-weight:{{($district->district_code == $active_tum) ? 'bold': ''}};" wire:click="$emit('regionClicked', '{{$district->district_code}}')"> 
                                                    {{ $key + 1 }}. {{ $district->district->name}} <i class='{{$class}}'>{{abs($district->diff)}}</i>
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
                        @endif
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
                            $string = 'Аҳоли кайфияти индекси (';
                        else if ($type == 'protests')
                            $string = "Оммавий норозилик бўлиш эҳтимоли (";
                        else if ($type == 'clusters')
                            $string = "Ҳудудлар кластери (";
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
        <div class="col-sm-4">
            <div class="stats" style="width: 100%; height:28vh;background:white;overflow:auto">
                <div class="card" style="box-shadow: none">
                    <div>
                        @if($type == 'mood' || $type == 'protests' || $type == 'clusters')
                        <table class="table" id="district_stat">
                            <thead class="thead-light" id="thead">
                                <tr>
                                  <th scope="col">#</th>
                                  <th scope="col">Индикатор @if ($type != 'clusters') <br> (ҳар 100 000 аҳолига) @endif </th>
                                  <th scope="col">Республикадаги ўртача қиймат <br></th>
                                  @if ($type == 'clusters')
                                      <th>Кластердаги ўртача қиймат <br></th>
                                  @endif
                                  <th scope="col">Тумандаги қиймат <br></th>
                                 </tr>
                            </thead>
                            <tbody id="indikatorlar">
                                @isset($indicators)
                                    @foreach ($indicators as $key=>$indicator)
                                        <tr>
                                            <td>{{$key + 1 }}</td>
                                            @if ($type != 'clusters')
                                                <td><a href="#" wire:click="openModal('{{$indicator->feature_name}}')">{{ $translates[$indicator->feature_name] }}</a></td>                                                
                                            @else
                                                <td>{{ $indicator->indicator }}</td>                                                                                                
                                            @endif
                                            <td>{{ number_format(round($indicator->average, 1 ), 1, ',', ' ') }}</td>
                                            @if ($type == 'clusters')
                                                <td>{{ number_format(round($indicator->clusterAverage, 1), 1, ',', ' ') }}</td>
                                            @endif
                                            <td>{{ number_format(round($indicator->value, 1), 1, ',', ' ') }}</td>                                
                                        </tr>
                                    @endforeach
                                @endisset
                            </tbody>
                        </table>
                        @elseif ($type == 'clusters')
                        
                        @else
                        <h5 class="card-header timeline">
                            @if ($activeRegion == 'republic')
                                {{ "Республика бўйича ".$translates[$activeIndicator] }} ({{$date}} ойи учун)                            
                            @else
                                {{ findRegion($activeRegion). " бўйича ".$translates[$activeIndicator] }} ({{$date}} ойи учун:)                                                        
                            @endif
                            {{-- {{ ($active_tum) ? $active_tum : "Республика бўйича ".$activeIndicator }} ({{$date}} ойи учун:) --}}
                        </h5>
                        <div class="row" style="padding: 10px">
                            <div class="col-md-6">
                                <h4 style="text-align: center; font-weight:100;float:right">Максимум қиймат:</h4>
                            </div>
                            <div class="col-md-6">
                                <h4><span style="color: rgb(68, 119, 170);font-size: 122.991%;">{{number_format( $top_districts->first()->score, 0, ',', ' ' )}}</span></h4>
                            </div>
                            {{-- <div class="col-md-6">
                                <h4 style="text-align: center; font-weight:100;float:right">Минимум қиймат:</h4>
                            </div>
                            <div class="col-md-6">
                                <h4><span style="color: rgb(68, 119, 170);font-size: 122.991%;">{{number_format( $top_districts->last()->score, 0, ',', ' ' )}}</span></h4>
                            </div> --}}
                            <div class="col-md-6">
                                <h4 style="text-align: center; font-weight:100;float:right">Умумий қиймат:</h4>
                            </div>
                            <div class="col-md-6">
                                <h4><span style="color: rgb(68, 119, 170);font-size: 122.991%;">{{number_format( $top_districts->sum('score'), 0, ',', ' ' ) }}</span></h4>
                            </div>
                            <div class="col-md-6">
                                <h4 style="text-align: center; font-weight:100;float:right">Ўртача қиймат:</h4>
                            </div>
                            <div class="col-md-6">
                                <h4><span style="color: rgb(68, 119, 170);font-size: 122.991%;">{{number_format( $top_districts->avg('score'), 0, ',', ' ' ) }}</span></h4>
                            </div>
                        </div>
                        @endif
                    </div>                        
                </div>
            </div>
        </div>
    </div>
    <hr>

    @prepend('scripts')
        <script>
            var mapOptions = {
                center: [41.311, 63.2505],
                zoom: 6,
                zoomControl: false,
                // dragging: false,
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

            var geojson = L.geoJSON(<?php echo json_encode($json); ?>, {
                style: function (feature) {
                    return style1(feature, <?php echo json_encode($top_districts[0]['score']); ?>, <?php echo json_encode($ranges); ?>);
                },
            }).addTo(map);
            geojson.eachLayer(function (layer) {
                layer.on('click', function(e) {
                    var element = document.getElementById(this.feature.properties.district_code);
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
                    Livewire.emit('regionClicked', layer['feature']['properties']['district_code']);
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
            var chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: 'Аҳоли кайфияти индекси башорати',
                        data: <?php echo json_encode($monthlyAvg); ?>,
                        borderWidth: 3,
                        borderColor: 'rgb(232, 9, 9)',
                        backgroundColor: 'rgb(232, 9, 9)',
                        yAxisID: 'y',
                    },
                    {
                        label: 'Аҳоли кайфияти индекси',
                        data: <?php echo json_encode($actualAvg); ?>,
                        borderWidth: 3,
                        borderColor: '#53a074',
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

            Livewire.on('changeTable', (tuman, data, actual, participants, dates, date, type) => {
                // var keys = Object.keys(geojson._layers);
                // var layer_id;
                // keys.forEach(function(key){
                //     if(geojson._layers[key].feature.properties.district_code == tuman)
                //         layer_id = key;
                // });

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
                        string = "<?php echo $activeIndicator ?>";
                        changeIndicatorChart(data, dates);
                        break;
                    case 'clusters':
                        changeClusterChart2(data, dates);
                        break;
                }
            });

            Livewire.on('updateMap', (type, json, top_districts, ranges) => {
                map.remove();
                map = L.map('map', mapOptions);
                if(type == 'mood'){
                    geojson = L.geoJSON(json, {
                        style: function (feature) {
                            return style1(feature, top_districts[0]['score'], ranges);
                        },
                    }).addTo(map);
                }else if(type == 'protests'){
                    geojson = L.geoJSON(json, {style: function (feature) {
                            return styleProtestMap(feature, top_districts[0]['score']);
                        },
                    }).addTo(map);
                    console.log(map);
                }
                else if(type == 'indicator'){
                    geojson = L.geoJSON(json, {
                        style: function (feature) {
                            return styleIndicator(feature, top_districts[0]['score']);
                        },
                    }).addTo(map);
                }
                else if(type == 'clusters'){
                    geojson = L.geoJSON(json, {
                        style: function (feature) {
                            return styleCluster(feature);
                        },
                    }).addTo(map);
                }

                geojson.eachLayer(function (layer) {
                    layer.on('click', function(e) {
                        element = document.getElementById(this.feature.properties.district_code);
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
                        Livewire.emit('regionClicked', layer['feature']['properties']['district_code']);
                    });
                });
            });

            Livewire.on('updateChart', (dates, data, actual, participants, type) => {
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

            Livewire.on('updateClusterChart', (dates, percentages, type) => {
                changeClusterChart(dates, percentages, type);
            });
        </script>    
    @endprepend
</div>
