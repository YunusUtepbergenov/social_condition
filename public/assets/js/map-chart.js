
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

var activeLayer = null;

if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)){
    mapOptions.dragging = false;
}

var map = L.map('map', mapOptions);

function getInitialStyle(feature) {
    switch (pageType) {
        case 'protests':
            return styleProtestMap(feature, topDistrictScore);
        case 'indicator':
            return styleIndicator(feature, topDistrictScore, topDistrictScoreMin);
        case 'clusters':
            return styleCluster(feature);
        default:
            return style1(feature, topDistrictScore, scoreRanges);
    }
}

var geojson = L.geoJSON(geoJsonData, {
    style: getInitialStyle,
}).addTo(map);

requestAnimationFrame(function() {
    map.invalidateSize();
    map.fitBounds(geojson.getBounds(), { animate: false, padding: [10, 10] });
});

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
        Livewire.dispatch('regionClicked', { tuman: layer['feature']['properties']['district_code'] });
    });
});

window.addEventListener('resize', function() {
    map.invalidateSize();
    map.fitBounds(geojson.getBounds(), { animate: false, padding: [10, 10] });
});

const ctx = document.getElementById('myChart1');

function getInitialChartConfig() {
    switch (pageType) {
        case 'protests':
            return {
                type: 'line',
                data: { labels: dates, datasets: [{ label: 'Оммавий норозилик содир бўлиши эҳтимоли', data: monthlyAvg, borderColor: chartColors.danger, pointBackgroundColor: chartColors.danger, fill: true, yAxisID: 'y' }, { type: 'bar', label: 'Оммавий норозиликлар сони', data: actualAvg, backgroundColor: colorToRgba(chartColors.primary, 0.5), yAxisID: 'y1' }] },
                options: { plugins: { legend: { display: false } }, interaction: { mode: 'index', intersect: false }, responsive: true, maintainAspectRatio: false, aspectRatio: 1, scales: { y: { beginAtZero: false, position: 'right' }, y1: { beginAtZero: true, position: 'left' } } }
            };
        case 'indicator':
            return {
                type: 'line',
                data: { labels: dates, datasets: [{ label: 'Кўрсаткич қиймати', data: monthlyAvg, borderColor: chartColors.primary, pointBackgroundColor: chartColors.primary, fill: true, yAxisID: 'y' }] },
                options: { plugins: { legend: { display: false } }, interaction: { mode: 'index', intersect: false }, responsive: true, maintainAspectRatio: false, aspectRatio: 1, scales: { y: { beginAtZero: false, position: 'left' } } }
            };
        case 'clusters':
            var clusterData = (typeof clusterPercentages !== 'undefined') ? clusterPercentages : [];
            var clusterYears = dates.map(function(d) { return new Date(d).getFullYear(); });
            return {
                type: 'line',
                data: { labels: clusterYears, datasets: [
                    { label: '1-тоифадаги туманлар', data: filterCluster(clusterData, 1), borderWidth: 0, fill: 'origin', backgroundColor: 'rgba(16, 185, 129, 0.8)', yAxisID: 'y' },
                    { label: '2-тоифадаги туманлар', data: filterCluster(clusterData, 2), borderWidth: 0, fill: 'origin', backgroundColor: 'rgba(139, 92, 246, 0.8)', yAxisID: 'y' },
                    { label: '3-тоифадаги туманлар', data: filterCluster(clusterData, 3), borderWidth: 0, fill: 'origin', backgroundColor: 'rgba(156, 163, 175, 0.8)', yAxisID: 'y' },
                    { label: '4-тоифадаги туманлар', data: filterCluster(clusterData, 4), borderWidth: 0, fill: 'origin', backgroundColor: 'rgba(245, 158, 11, 0.8)', yAxisID: 'y' },
                    { label: '5-тоифадаги туманлар', data: filterCluster(clusterData, 5), borderWidth: 0, fill: 'origin', backgroundColor: 'rgba(59, 130, 246, 0.8)', yAxisID: 'y' }
                ] },
                options: { responsive: true, maintainAspectRatio: false, aspectRatio: 1, interaction: { intersect: false }, scales: { y: { stacked: true, position: 'left', max: 100 }, x: { stacked: true } }, plugins: { legend: { display: true }, filler: { propagate: false } } }
            };
        default:
            return {
                type: 'line',
                data: { labels: dates, datasets: [{ label: 'Истеъмолчилар кайфияти индекси башорати', data: monthlyAvg, borderColor: chartColors.primary, pointBackgroundColor: chartColors.primary, fill: true, yAxisID: 'y' }, { label: 'Истеъмолчилар кайфияти индекси', data: actualAvg, borderColor: chartColors.secondary, pointBackgroundColor: chartColors.secondary, fill: true, yAxisID: 'y' }] },
                options: { plugins: { legend: { display: true } }, interaction: { mode: 'index', intersect: false }, responsive: true, maintainAspectRatio: false, aspectRatio: 1, scales: { y: { beginAtZero: false, position: 'left', ticks: { stepSize: 0.25 } } } }
            };
    }
}

var chart = new Chart(ctx, getInitialChartConfig());

Livewire.on('changeTable', ({ tuman, data, actual, participants, dates, date, type }) => {
    var keys = Object.keys(geojson._layers);
    var layer_id;
    if (activeLayer) {
        resetLayerStyle(activeLayer);
    }
    for (var key of keys){
        if(geojson._layers[key].feature.properties.district_code == tuman){
            layer_id = key;
            break;
        }
    };

    activeLayer = geojson._layers[layer_id];
    if (activeLayer) {
         activeLayer.setStyle({
            weight: 1,
            color: '#000',
            fillOpacity: 1
        });   
    }    

    var string = '';
    switch (type) {
        case 'mood':
            string = 'Истеъмолчилар кайфияти ';
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

function resetLayerStyle(layer) {
    if (layer instanceof L.Path) {
        layer.setStyle({
            weight: 1,
            color: '#fff',
            fillOpacity: 1
        });
    } else if (layer instanceof L.Marker) {
        layer.setZIndexOffset(0);
    }
}

Livewire.on('updateMap', ({ type, json, top_districts, ranges }) => {
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
    }
    else if(type == 'indicator'){
        geojson = L.geoJSON(json, {
            style: function (feature) {
                return styleIndicator(feature, top_districts[0]['score'], top_districts[top_districts.length - 1]['score']);
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
            Livewire.dispatch('regionClicked', { tuman: layer['feature']['properties']['district_code'] });
        });
    });

    requestAnimationFrame(function() {
        map.invalidateSize();
        map.fitBounds(geojson.getBounds(), { animate: false, padding: [10, 10] });
    });
});

Livewire.on('updateChart', ({ dates, data, actual, participants, type }) => {
    var string = '';
    switch (type) {
        case 'mood':
            string = 'Истеъмолчилар кайфияти ';
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