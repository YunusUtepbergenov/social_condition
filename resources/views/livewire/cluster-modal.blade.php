<div>
    <div class="modal fade" id="clustermodal" tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
        <div class="modal-dialog">
           <div class="modal-content">
                <div class="modal-header box-shadow-1">
                    <h5 class="card-header">@isset($activeIndicator) {{$translates[$activeIndicator]}} ({{findDistrict($activeDistrict)}}) @endisset</h5>
                    <button type="button" class="close" wire:click="$emit('closeClusterModal')" style="background-color: #c9c9c9" aria-label="{{ __('Close') }}">
                        <span aria-hidden="true"><strong>&times;</strong></span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="row" style="margin-bottom: 20px">
                            <div class="col-sm-12">
                                <div class="chart-meta">
                                    <p>{{$date}}</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body" style="position: relative;padding: 0 1.5rem; height: 28vh;width:100%;">
                            <div class="wrapper2">
                                <canvas id="clusterChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            @isset($activeIndicator)
                                <table class="table table-bordered" id="modal_table">
                                    <thead>
                                        <th>Кўрсаткич</th>
                                        <th>Қиймат</th>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Туман кўрсаткичи</td>
                                            <td>{{ number_format( $curVal, 1, ',', ' ' ) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Республика бўйича ўртача кўрсаткич</td>
                                            <td>{{number_format($repAvg['score'], 1, ',', ' ' )}}</td>
                                        </tr>
                                        <tr>
                                            <td>Вилоят бўйича ўртача кўрсаткич</td>
                                            <td>{{number_format($vilAvg['score'], 1, ',', ' ' )}}</td>
                                        </tr>
                                        <tr>
                                            <td>Бир йил олдинги кўрсаткич</td>
                                            <td>{{number_format($lastYear, 1, ',', ' ' ) }}</td>
                                        </tr>
                                        @if ($lastYear)
                                            <tr>
                                                <td>Бир йил олдинги кўрсаткичга нисбатан ўсиш</td>
                                                <td colspan="2">{{number_format(($curVal - $lastYear) * 100 / abs($lastYear), 1, ',', ' ' ) }}%</td>
                                            </tr>
                                        @endif
                                        @if ($ovrReg['feature'])
                                            <tr>
                                                <td>Вилоятдаги улуши</td>
                                                <td colspan="2">{{number_format( ($curVal / $ovrReg['feature']) * 100 , 1, ',', ' ' ) }}%</td>
                                            </tr>
                                        @endif
                                        @if ($ovrRep['feature'])
                                            <tr>
                                                <td>Республикадаги улуши</td>
                                                <td colspan="2">{{number_format( ($curVal / $ovrRep['feature']) * 100 , 1, ',', ' ' ) }}%</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            @endisset
                        </div>
                    </div>
                </div>
            </div>
      </div>
    </div>
</div>

<style>
    #modal_table th {
        font-size: 14px;
    }

    #modal_table td {
        font-size: 14px;
    }
</style>

@prepend('scripts')
    <script>
        var ctx3 = document.getElementById('clusterChart');
        var clusterChart = new Chart(ctx3, {
            type: 'line',
            data: {},
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

        window.addEventListener('openClusterModal', event => {
            $("#clustermodal").modal('show');

        });

        Livewire.on('closeClusterModal', () => {
            $("#clustermodal").modal('hide');
        });

        Livewire.on('buildClusterChart', (dataNominal, dates)=>{
            console.log('eeeee');
            console.log(dataNominal);
            clusterChart.data = {
                labels: dates,
                datasets: [{
                    label: 'Кўрсаткич қиймати',
                    data: dataNominal,
                    borderWidth: 2,
                    borderColor: 'rgb(68, 119, 170)',
                    backgroundColor: '#bbdefb',
                    yAxisID: 'y',
                }],
            };

            clusterChart.options = {
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
                        ticks: {
                            stepSize: 0.25
                        },
                    },
                }
            }

            clusterChart.update('none');
        });
    </script>
@endprepend
