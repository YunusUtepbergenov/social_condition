
function styleRegion(feature) {
    if(feature.factors == undefined ){
        num = -1
        label = null;
    }else{
        label = feature.factors.cluster;
    }
    return {
        fillColor: getColor(label),
        weight: 0.8,
        opacity: 1,
        color: 'white',
        dashArray: '0',
        fillOpacity: 1
    };
}

function getColor(cluster) {
    if(cluster == undefined){
        return '#bababa'}
    else{
        if(cluster == 1){
            return 'rgb(46, 204, 113)'
        }
        else if (cluster == 2) {
            return 'rgb(52, 152, 219)'
        }
        else if (cluster == 3){
            return 'rgb(44,62,80)'
        }
        else if (cluster == 4) {
            return 'rgb(243, 156, 18)'
        }
        else if (cluster == 5){
            return 'rgb(231, 76, 60)'
        }
    }
}
