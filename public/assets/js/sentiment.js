function styleSentimentMap(feature, ranges) {
    if(feature.factors == undefined ){
        num = -1
        label = null;
    }else{
        console.log(ranges);
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
            return 'rgb(68, 119, 170,' + d + ' )'
        }
        else if (labell == 2) {
            return 'rgb(115, 115, 115,' + d + ' )'
        }
        else if (labell == 3){
            return 'rgb(4, 117, 53,' + d + ' )'
        }
    }
}



function styleSentimentIndicatorMap(feature, max, min){
    if(feature.factors == undefined ){
        num = -1
        label = null;
    }else{
        score_val = feature.factors.score;
        num = scale(score_val, max, min, 1, 0.2);
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

function getSentimentColor(d) {
    if(num == -1){
        return '#bababa'}
    else{
      return 'rgb(68, 119, 170,' + d + ' )'
    }
}

function changeSentimentChart(data, dates, repAvg){
    datasets = calcDatasets(data, repAvg);
    chart.data = {
        labels: dates,
        datasets: datasets,
    }
    chart.options = {
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

function calcDatasets(data, repAvg){
    if(repAvg == null){
        return [
            {
                label: 'Республик бўйича',
                data: data,
                borderWidth: 2,
                borderColor: 'rgb(68, 119, 170)',
                backgroundColor: '#bbdefb',
                yAxisID: 'y',
            }
        ]
    }else{
        return [
            {
                label: 'Вилоят бўйича',
                data: data,
                borderWidth: 2,
                borderColor: 'rgb(68, 119, 170)',
                backgroundColor: '#bbdefb',
                yAxisID: 'y',
            },
            {
                label: 'Республика бўйича',
                data: repAvg,
                borderWidth: 2,
                borderColor: 'red',
                backgroundColor: 'red',
                yAxisID: 'y',
            }
        ]
    }
}

function changeIndicatorChart(data, dates, repAvg){
    datasets = calcDatasets(data, repAvg);
    chart.data = {
        labels: dates,
        datasets: datasets,
    }
    chart.options = {
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
