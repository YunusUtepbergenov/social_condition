// const { result } = require("lodash");

function style(feature, max) {
    if(feature.factors.score == undefined ){
        num = -1;
        label = null;
    }else{
        label = date_data['label'];
        score_val = feature.factors.score;

        if(label == 1){
            num = scale(score_val, date_data['neg_range'][0], date_data['neg_range'][1], 1, 0.5);
        }else if (label == 2){
            num = scale(score_val, date_data['neu_range'][0], date_data['neu_range'][1], 0.5, 1); 
        }else if(label == 3){
            num = scale(score_val, date_data['pos_range'][0], date_data['pos_range'][1], 0.7, 1); 
        }
        // num = scale(label, feature.label_neg_min, feature.label_neg_max, 0.1, 1);
    }
    return {
        fillColor: getColor(num, label),
        weight: 1,
        opacity: 1,
        color: 'white',
        dashArray: '3',
        fillOpacity: 0.7
    };
}

function style1(feature, max, ranges) {
    if(feature.factors == undefined ){
        num = -1
        label = null;
    }else{
        range = ranges[0];
        score_val = feature.factors.score;
        label = feature.factors.label;

        if(label == 1){
            num = scale(score_val, range['neg_range_from'], range['neg_range_to'], 1, 0.5);
        }else if (label == 2){
            num = scale(score_val, range['neu_range_from'], range['neu_range_to'], 0.5, 1); 
        }else if(label == 3){
            num = scale(score_val, range['pos_range_from'], range['pos_range_to'], 0.7, 1); 
        }
    }
    return {
        fillColor: getColor(num, label),
        weight: 1,
        opacity: 1,
        color: 'white',
        dashArray: '0',
        fillOpacity: 1
    };
}

function getColor(d, labell) {
    if(num == -1){
        return '#bababa'}
    else{
        if(labell == 1){
            return 'rgb(255, 0, 0,' + d + ' )'
        }
        else if (labell == 2) {
            return 'rgb(115, 115, 115,' + d + ' )'             
        }
        else if (labell == 3){
            return 'rgb(4, 117, 53,' + d + ' )'        
        } 
    }
}

function scale (number, inMin, inMax, outMin, outMax) {
    return (number - inMin) * (outMax - outMin) / (inMax - inMin) + outMin;
}

function changeTable(data){
    tbody = $('#indikatorlar');
    tbody.html('');

    if (checkIfEmpty(data)){
        return
    }
    type = 'mi'

    for (let i = 0; i < data[type]['names'].length; i++) {
        if( data[type]['names'][i] != '-' && data[type]['names'][i] != undefined){
            tbody.append("<tr><td scope='row'>" + (i + 1) + "</td><td>" + data[type]['names'][i] + "</td><td>" + data[type]['units'][i] +"</td><td>" + data[type]['avg'][i] + "</td><td>" + data[type]['values'][i] +"</td></tr>")
        }
    }
}

function checkIfEmpty(array) {
    return Array.isArray(array) && (array.length == 0 || array.every(checkIfEmpty));
}

function changeTableContentsandChart(data, actual, dates, type, label){
    chart.data = {
        labels: dates,
        datasets: [{
            label: label + 'индекси башорат',
            data: data,
            borderWidth: 3,
            borderColor: 'rgb(232, 9, 9)',
            backgroundColor: 'rgb(232, 9, 9)',
            yAxisID: 'y',
        },
        {
            label: label + 'индекси',
            data: actual,
            borderWidth: 3,
            borderColor: '#53a074',
            backgroundColor: '#bbdefb',
            yAxisID: 'y',
        }],
    }
    chart.options = {
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
            },
        },
    }

    chart.update('none');
}

function changeProtestChart(data, actual, dates, type, label, participants){
    max_participants = Math.max(...actual);
    colors = actual.map(value => {
        opacity = scale(value, max_participants, 0, 1, 0.1);
        return 'rgb(68, 119, 170,' + opacity + ' )';
    });

    chart.data = {
        labels: dates,
        datasets: [{
            type: 'line',
            label: label,
            data: data,
            borderWidth: 2,
            borderColor: 'rgb(232, 9, 9)',
            backgroundColor: '#fff',
            yAxisID: 'y',
        },
        {
            type: 'bar',
            label: 'Оммавий норозиликлар сони',
            data: actual,
            borderWidth: 2,
            borderColor: 'black',
            backgroundColor: colors,
            yAxisID: 'y1',
        },
    ],
    }
    chart.options = {
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                  label: function (context) {
                    if(context.dataset.label == 'Оммавий норозиликлар сони')
                        return context.dataset.label + ': ' + context.formattedValue + ' (' + participants[context.dataIndex] + ' қатнашувчи)';
                    else if (context.dataset.label == label)
                        return context.dataset.label + ': ' + context.formattedValue + '%';
                  }
                }
            }
        },
        interaction : {
            mode: 'index',
        },
        responsive: true,
        maintainAspectRatio: false,
        aspectRatio: 1,
        scales: {
            y: {
                beginAtZero: false,
                position: 'left',
            },
            y1: {
                beginAtZero: true,
                position: 'right',
            },
        },
    }

    chart.update('none');
}

function changeIndicatorChart(data, dates){
    chart.data = {
        labels: dates,
        datasets: [{
            label: 'Кўрсаткич қиймати',
            data: data,
            borderWidth: 2,
            borderColor: 'rgb(68, 119, 170)',
            backgroundColor: '#bbdefb',
            yAxisID: 'y',
        }],
    }
    chart.options = {
        plugins: {
            legend: {
                display: false
            },
        },
        responsive: true,
        maintainAspectRatio: false,
        aspectRatio: 1,
        scales: {
            y: {
                beginAtZero: false,
                position: 'left',
            },
        },
    }

    chart.update('none');
}

function changeClusterChart2(data, dates){
    chart.data = {
        labels: dates,
        datasets: [{
            label: 'Кўрсаткич қиймати',
            data: data,
            borderWidth: 2,
            borderColor: 'rgb(68, 119, 170)',
            backgroundColor: '#bbdefb',
            yAxisID: 'y',
        }],
    }
    chart.options = {
        plugins: {
            legend: {
                display: false
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                position: 'left',
                min: 0,
                max: 6,
                ticks: {
                    stepSize: 1, // Show ticks for every 1 unit
                }
            },
        },
    }

    chart.update('none');
}

function changeClusterChart(dates, percentages, type){
    let years = dates.map(date => new Date(date).getFullYear());
    chart.data = {
        labels: years,
        datasets: [
            {
                label: '1-кластердаги туманлар',
                data: filterCluster(percentages, 1),
                borderWidth: 2,
                fill: 'origin',
                borderColor: '#fff',
                backgroundColor: 'rgb(115, 182, 107)',
                yAxisID: 'y',
            },
            {
                label: '2-кластердаги туманлар',
                data: filterCluster(percentages, 2),
                borderWidth: 2,
                fill: 'origin',
                borderColor: '#fff',
                backgroundColor: 'rgb(41, 162, 198)',
                yAxisID: 'y',
            },
            {
                label: '3-кластердаги туманлар',
                data: filterCluster(percentages, 3),
                borderWidth: 2,
                fill: 'origin',
                borderColor: '#fff',
                backgroundColor: 'rgb(160, 160, 160)',
                yAxisID: 'y',
            },
            {
                label: '4-кластердаги туманлар',
                data: filterCluster(percentages, 4),
                borderWidth: 2,
                fill: 'origin',
                borderColor: '#fff',
                backgroundColor: 'rgb(250, 167, 63)',
                yAxisID: 'y',
            },
            {
                label: '5-кластердаги туманлар',
                data: filterCluster(percentages, 5),
                borderWidth: 2,
                fill: 'origin',
                borderColor: '#fff',
                backgroundColor: 'rgb(220, 85, 100)',
                yAxisID: 'y',
            }
        ],
    }
    chart.options = {
        responsive: true,
        maintainAspectRatio: false,
        aspectRatio: 1,
        interaction: { 
            intersect: false, 
        },
        scales: {
            y: {
                stacked: true,
                beginAtZero: true,
                position: 'left',
                ticks: {
                    percentage: true
                }
            },
        },
    }

    chart.update('none');
}

function styleProtestMap(feature, max) {
    if(feature.factors == undefined ){
        color = -1;
    }else{
        score_val = feature.factors.score;
        if(score_val > 75){
            color = 'rgb(232,9,9)';
        }else if(score_val >= 50 && score_val <= 75){
            color = 'rgb(115, 115, 115)';
        }else{
            color = 'rgb(4, 157, 60)';
        }
    }
    return {
        fillColor: getProtestColor(color),
        weight: 1,
        opacity: 1,
        color: 'white',
        dashArray: '0',
        fillOpacity: 1
    };
}

function getProtestColor(d) {
    if(d == -1)
        return '#bababa';
    else
        return d;
    //   return 'rgb(232, 9, 9,' + d + ' )'
}

function filterCluster(data, cluster){
    var result = data.filter(function($item){
        return $item.cluster_id == cluster;
    });

    result = result.map(function(item){
        return item.total;
    });

    return result;
}

function styleIndicator(feature, max){
    if(feature.factors == undefined ){
        num = -1
        label = null;
    }else{
        score_val = feature.factors.score;
        num = scale(score_val, max, 0, 1 , 0.2);
    }

    return {
        fillColor: getIndicatorColor(num),
        weight: 1,
        opacity: 1,
        color: 'white',
        dashArray: '0',
        fillOpacity: 1
    };
}
function styleCluster(feature){
    if(feature.factors == undefined){
        label = -1;
    }else{
        label = feature.factors.score;
    }

    return {
        fillColor: getClusterColor(label),
        weight: 1,
        opacity: 1,
        color: 'white',
        dashArray: '0',
        fillOpacity: 1
    };
}

function getIndicatorColor(d) {
    if(num == -1){
        return '#bababa'}
    else{
      return 'rgb(68, 119, 170,' + d + ' )'
    }
}
function getClusterColor(d) {
    if(d == -1){
        return '#bababa';
    }
    else if (d == 1){
        return 'rgb(115, 182, 107,' + d + ' )' 
    }
    else if (d == 2){
        return 'rgb(41, 162, 198,' + d + ' )'
    }
    else if (d == 3){
        return 'rgb(160, 160, 160,' + d + ' )'
    }
    else if (d == 4){
        return 'rgb(250, 167, 63,' + d + ' )'
    }
    else if (d == 5){
        return 'rgb(220, 85, 100,' + d + ' )'
    }
}

function changeProtestsTable(data){
    tbody = $('#indikatorlar');
    tbody.html('');

    if (checkIfEmpty(data) || $.isEmptyObject(data)){
        return
    }
    for (let i = 0; i < data['names'].length; i++) {
        if( data['names'][i] != '-' && data['names'][i] != undefined){
            tbody.append("<tr><td scope='row'>" + (i + 1) + "</td><td>" + data['names'][i] + "</td><td>" + (data['pct_change'][i] * 100).toFixed(1) +"</td><td>" + data['avg'][i] + "</td><td>" + data['values'][i] +"</td></tr>")
        }
    }
}