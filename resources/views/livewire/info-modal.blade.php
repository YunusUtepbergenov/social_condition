<div>
    @php
        $months = array(
            "January" => "январь",
            "February" => "февраль",
            "March" => "март",
            "April" => "апрель",
            "May" => "май",
            "June" => "июнь",
            "July" => "июль",
            "August" => "август",
            "September" => "сентябрь",
            "October" => "октябрь",
            "November" => "ноябрь",
            "December" => "декабрь"
        );
    @endphp
    <div class="modal fade" id="infomodal" tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
        <div class="modal-dialog">
           <div class="modal-content">
                <div class="modal-header box-shadow-1">
                    <h5 class="card-header">@isset($activeIndicator) {{$translates[$activeIndicator]}} ({{findDistrict($activeDistrict)}}) @endisset</h5>
                    <button type="button" class="close" wire:click="$emit('closeFormModal')" style="background-color: #c9c9c9" aria-label="{{ __('Close') }}">
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
                                <canvas id="linechart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            @isset($activeIndicator)
                                <table class="table table-bordered" id="modal_table">
                                    <thead>
                                        <th>Кўрсаткич номи</th>
                                        <th>Кўрсаткич</th>
                                        <th>Кўрсаткич (ҳар 100 000 аҳолига)</th>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Туман кўрсаткичи</td>
                                            <td>{{ numberToWords($curVal) }}</td>
                                            <td>{{numberToWords($curValNor)}}</td>
                                        </tr>
                                        <tr>
                                            <td>Республика кўрсаткичи</td>
                                            <td>{{numberToWords($repAvg['score'])}}</td>
                                            <td>{{numberToWords($repAvgNor['score'])}}</td>
                                        </tr>
                                        <tr>
                                            <td>Вилоят кўрсаткичи</td>
                                            <td>{{numberToWords($vilAvg['score'])}}</td>
                                            <td>{{numberToWords($vilAvgNor['score'])}}</td>
                                        </tr>
                                        <tr>
                                            <td>Ўтган ой кўрсаткичи</td>
                                            <td>{{numberToWords($lastMonth) }}</td>
                                            <td>{{numberToWords($lastMonthNor) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Бир йил олдинги кўрсаткич</td>
                                            <td>{{numberToWords($lastYear) }}</td>
                                            <td>{{numberToWords($lastYearNor) }}</td>
                                        </tr>

                                        @if ($lastMonth != 0)
                                            <tr>
                                                <td>Ўтган ойга нисбатан ўсиш</td>
                                                <td colspan="2">{{numberToWords( ($curVal - $lastMonth) * 100 / abs($lastMonth)) }}%</td>
                                            </tr>
                                        @endif
                                        @if ($lastYear)
                                            <tr>
                                                <td>Бир йил олдинга нисбатан ўсиш</td>
                                                <td colspan="2">{{numberToWords(($curVal - $lastYear) * 100 / abs($lastYear))}}%</td>
                                            </tr>
                                        @endif
                                        @if ( date('F', strtotime($date)) != "January")
                                            <tr>
                                                <td>{{date('Y', strtotime($date))}} йил январь - {{$months[date('F', strtotime($date))]}} </td>
                                                <td>{{numberToWords($cumilativeThisYear['feature']) }}</td>
                                                <td>{{numberToWords($cumilativeThisYearNor['feature']) }}</td>
                                            </tr>
                                            <tr>
                                                <td>{{date('Y', strtotime($lastYearDate))}} йил январь - {{$months[date('F', strtotime($lastYearDate))]}}</td>
                                                <td>{{numberToWords($cumilativeLastYear['feature']) }}</td>
                                                <td>{{numberToWords($cumilativeLastYearNor['feature']) }}</td>
                                            </tr>
                                        @endif
                                        @if ($ovrReg['feature'])
                                            <tr>
                                                <td>Вилоятдаги улуши</td>
                                                <td colspan="2">{{numberToWords( ($curVal / $ovrReg['feature']) * 100) }}%</td>
                                            </tr>
                                        @endif
                                        @if ($ovrRep['feature'])
                                            <tr>
                                                <td>Республикадаги улуши</td>
                                                <td colspan="2">{{numberToWords( ($curVal / $ovrRep['feature']) * 100) }}%</td>
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
                                let number = Number(dataAvg[context.dataIndex]);
                                let formattedNumber = number.toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1});
                                return [context.dataset.label + ': ' + context.formattedValue, ' Ҳар 100 000 аҳолига: ' + formattedNumber ];
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
            };

            chart12.update('none');
        });
    </script>
@endprepend
