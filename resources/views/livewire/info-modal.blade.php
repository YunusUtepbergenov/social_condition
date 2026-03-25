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
                <div class="info-modal-header">
                    <h5 class="info-modal-title">@isset($activeIndicator) {{$translates[$activeIndicator]}} <span class="info-modal-district">({{findDistrict($activeDistrict)}})</span> @endisset</h5>
                    <button type="button" class="info-modal-close" wire:click="$dispatch('closeFormModal')" aria-label="{{ __('Close') }}">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 1L13 13M1 13L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
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
                    @isset($activeIndicator)
                    <div class="info-table-wrapper mt-3">
                        <table class="table table-sm mb-0" id="modal_table">
                            <thead>
                                <tr>
                                    <th>Кўрсаткич номи</th>
                                    <th class="text-right">Кўрсаткич</th>
                                    <th class="text-right">Ҳар 100 000 аҳолига</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="row-highlight">
                                    <td class="font-weight-bold">Туман кўрсаткичи</td>
                                    <td class="text-right font-weight-bold val-primary">{{ numberToWords($curVal) }}</td>
                                    <td class="text-right font-weight-bold val-primary">{{ $curValNor !== null ? numberToWords($curValNor) : '—' }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        Республика ўртача
                                        <small class="d-block text-muted font-italic">Туманлар бўйича ўртача</small>
                                    </td>
                                    <td class="text-right">{{ $repAvg ? numberToWords($repAvg['score']) : '—' }}</td>
                                    <td class="text-right">{{ $repAvgNor ? numberToWords($repAvgNor['score']) : '—' }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        Вилоят кўрсаткичи
                                        <small class="d-block text-muted font-italic">Туманлар бўйича ўртача</small>
                                    </td>
                                    <td class="text-right">{{ $vilAvg ? numberToWords($vilAvg['score']) : '—' }}</td>
                                    <td class="text-right">{{ $vilAvgNor ? numberToWords($vilAvgNor['score']) : '—' }}</td>
                                </tr>
                                <tr>
                                    <td>Ўтган ой кўрсаткичи</td>
                                    <td class="text-right">{{ $lastMonth !== null ? numberToWords($lastMonth) : '—' }}</td>
                                    <td class="text-right">{{ $lastMonthNor !== null ? numberToWords($lastMonthNor) : '—' }}</td>
                                </tr>
                                <tr>
                                    <td>Бир йил олдинги кўрсаткич</td>
                                    <td class="text-right">{{ $lastYear !== null ? numberToWords($lastYear) : '—' }}</td>
                                    <td class="text-right">{{ $lastYearNor !== null ? numberToWords($lastYearNor) : '—' }}</td>
                                </tr>
                                @if ($lastMonth && $lastMonth != 0)
                                    @php $monthGrowth = round(($curVal - $lastMonth) * 100 / abs($lastMonth), 1); @endphp
                                    <tr class="row-growth">
                                        <td class="font-weight-bold">Ўтган ойга нисбатан ўсиш</td>
                                        <td colspan="2" class="text-right font-weight-bold {{ $monthGrowth >= 0 ? 'val-positive' : 'val-negative' }}">
                                            {{ $monthGrowth >= 0 ? '+' : '' }}{{ $monthGrowth }}%
                                        </td>
                                    </tr>
                                @endif
                                @if ($lastYear && $lastYear != 0)
                                    @php $yearGrowth = round(($curVal - $lastYear) * 100 / abs($lastYear), 1); @endphp
                                    <tr class="row-growth">
                                        <td class="font-weight-bold">Бир йил олдинга нисбатан ўсиш</td>
                                        <td colspan="2" class="text-right font-weight-bold {{ $yearGrowth >= 0 ? 'val-positive' : 'val-negative' }}">
                                            {{ $yearGrowth >= 0 ? '+' : '' }}{{ $yearGrowth }}%
                                        </td>
                                    </tr>
                                @endif
                                @if (date('F', strtotime($date)) != "January")
                                    <tr>
                                        <td>{{ date('Y', strtotime($date)) }} йил январь - {{ $months[date('F', strtotime($date))] }}</td>
                                        <td class="text-right">{{ numberToWords($cumilativeThisYear['feature']) }}</td>
                                        <td class="text-right">{{ numberToWords($cumilativeThisYearNor['feature']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ date('Y', strtotime($lastYearDate)) }} йил январь - {{ $months[date('F', strtotime($lastYearDate))] }}</td>
                                        <td class="text-right">{{ numberToWords($cumilativeLastYear['feature']) }}</td>
                                        <td class="text-right">{{ numberToWords($cumilativeLastYearNor['feature']) }}</td>
                                    </tr>
                                @endif
                                @if ($ovrReg && $ovrReg['feature'])
                                    <tr>
                                        <td>Вилоятдаги улуши</td>
                                        <td colspan="2" class="text-right">{{ numberToWords(($curVal / $ovrReg['feature']) * 100) }}%</td>
                                    </tr>
                                @endif
                                @if ($ovrRep && $ovrRep['feature'])
                                    <tr>
                                        <td>Республикадаги улуши</td>
                                        <td colspan="2" class="text-right">{{ numberToWords(($curVal / $ovrRep['feature']) * 100) }}%</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    @endisset
                </div>
            </div>
      </div>
    </div>

    <style>
        /* Modal font consistency */
        #infomodal .modal-content {
            font-family: 'Inter', sans-serif;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        /* Header */
        .info-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: #3f4b5b;
            border: none;
        }

        .info-modal-title {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            margin: 0;
            line-height: 1.4;
        }

        .info-modal-district {
            font-weight: 400;
            opacity: 0.8;
        }

        /* Close button */
        .info-modal-close {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
            flex-shrink: 0;
            margin-left: 12px;
            padding: 0;
        }

        .info-modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .info-modal-close:active {
            transform: scale(0.95);
        }

        /* Modal body */
        #infomodal .modal-body {
            padding: 20px;
            font-family: 'Inter', sans-serif;
        }

        /* Table */
        .info-table-wrapper {
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }

        #modal_table {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            margin: 0;
        }

        #modal_table thead th {
            background: #3f4b5b;
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            border: none;
        }

        #modal_table tbody td {
            font-family: 'Inter', sans-serif;
            padding: 9px 12px;
            color: #4a5568;
            border-top: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        #modal_table tbody tr:hover {
            background-color: #f8fafc;
        }

        #modal_table .row-highlight {
            background-color: #eff6ff;
        }

        #modal_table .row-highlight:hover {
            background-color: #dbeafe;
        }

        #modal_table .row-highlight td {
            border-top: 1px solid #bfdbfe;
        }

        #modal_table .row-growth {
            background-color: #f8fafc;
        }

        #modal_table .val-primary {
            color: #2563eb;
        }

        #modal_table .val-positive {
            color: #16a34a;
        }

        #modal_table .val-negative {
            color: #dc2626;
        }
    </style>

    @script
    <script>
        (function() {
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

        Livewire.on('openFormModal', () => {
            $("#infomodal").modal('show');
        });

        Livewire.on('closeFormModal', () => {
            $("#infomodal").modal('hide');
        });

        Livewire.on('buildCharts', ({ data: dataNominal, dataAvg, dates })=>{
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
        })();
    </script>
    @endscript
</div>
