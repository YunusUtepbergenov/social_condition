<div>
    <div class="modal fade" id="infomodal" tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
        <div class="modal-dialog">		
           <div class="modal-content">
              <div class="modal-header box-shadow-1" style="text-shadow: 1px 1px 2px #7DA0B1">
                  <button type="button" class="close" wire:click="$emit('closeFormModal')" style="background-color: #c9c9c9" aria-label="{{ __('Close') }}">
                      <span aria-hidden="true"><strong>&times;</strong></span>
                  </button>
              </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="row">
                            <div class="col-sm-12">
                                <h5 class="card-header timeline">@isset($activeIndicator) {{$translates[$activeIndicator]}} ({{findDistrict($activeDistrict)}}) @endisset</h5>
                            </div>                        
                        </div>
                        <div class="card-body" style="position: relative;padding: 0 1.5rem; height: 25vh;width:100%;">
                            <div class="wrapper2">
                                <canvas id="linechart"></canvas>
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
                                        <th>Қиймат (ҳар 100 000 аҳолига)</th>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Тумандаги қиймат</td>
                                            <td>{{ number_format( $curVal[$activeIndicator], 1, ',', ' ' ) }}</td>
                                            <td>{{number_format($curValNor['score'], 1, ',', ' ' )}}</td>
                                        </tr>    
                                        <tr>
                                            <td>Республика бўйича ўртача</td>
                                            <td>{{number_format($repAvg['score'], 1, ',', ' ' )}}</td>
                                            <td>{{number_format($repAvgNor['score'], 1, ',', ' ' )}}</td>
                                        </tr>
                                        <tr>
                                            <td>Вилоят бўйича ўртача</td>
                                            <td>{{number_format($vilAvg['score'], 1, ',', ' ' )}}</td>
                                            <td>{{number_format($vilAvgNor['score'], 1, ',', ' ' )}}</td>
                                        </tr>
                                        <tr>
                                            <td>Ўтган ойдаги қиймат</td>
                                            <td>{{number_format($lastMonth[$activeIndicator], 1, ',', ' ' ) }}</td>
                                            <td>{{number_format($lastMonthNor['score'], 1, ',', ' ' ) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Ўтган йилги қиймат</td>
                                            <td>{{number_format($lastYear[$activeIndicator], 1, ',', ' ' ) }}</td>
                                            <td>{{number_format($lastYearNor['score'], 1, ',', ' ' ) }}</td>
                                        </tr>                                        
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
        var ctx3 = document.getElementById('linechart');
        var chart12 = new Chart(ctx3, { 
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

        window.addEventListener('openFormModal', event => {
            $("#infomodal").modal('show');

        });

        Livewire.on('closeFormModal', () => {
            $("#infomodal").modal('hide');
        });

        Livewire.on('buildCharts', (dataNominal, dataAvg, dates)=>{
            chart12.data = {
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

            chart12.options = {
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                var roundedValue = Number(dataAvg[context.dataIndex]).toFixed(1);
                                return [context.dataset.label + ': ' + context.formattedValue, ' Ҳар 100 000 аҳолига: ' + roundedValue ];
                            }
                        }
                    }
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

            chart12.update('none');
        });
    </script>    
@endprepend
