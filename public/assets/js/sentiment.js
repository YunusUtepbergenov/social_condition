function styleSentimentMap(feature) {
    if(feature.factors == undefined ){
        color = -1;
    }else{
        score_val = feature.factors.score;
        color = feature.factors.color;
    }
    return {
        fillColor: color,
        weight: 1,
        opacity: 1,
        color: 'white',
        dashArray: '0',
        fillOpacity: 1
    };
}

function styleSentimentIndicatorMap(feature, max){

    if(feature.factors == undefined ){
        num = -1
        label = null;
    }else{
        score_val = feature.factors.score;
        num = scale(score_val, max, 0, 1, 0.2);
        console.log(num);
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

function changeSentimentChart(data, dates){
    chart.data = {
        labels: dates,
        datasets: [{
            label: 'Аҳоли кайфияти',
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

function changeIndicatorChart(data, dates, indicator){
    console.log(data);
    console.log(dates);
    chart.data = {
        labels: dates,
        datasets: [{
            label: "Кўрсаткич қиймати",
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
