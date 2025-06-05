<div class="col-sm-5">
    <div class="stats" style="width: 100%; height:28vh; background:white; overflow:auto">
        <div class="card" style="box-shadow: none">
            <div>
                <table class="table" id="district_stat">
                    <thead class="thead-light" id="thead">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">
                                Индикатор
                                @if ($type !== 'clusters')
                                    <br> (ҳар 100 000 аҳолига)
                                @endif
                            </th>
                            <th scope="col">Республика кўрсаткичи <br></th>
                            @if ($type === 'clusters')
                                <th>Тоифа кўрсаткичи <br></th>
                            @endif
                            <th scope="col">Туман кўрсаткичи <br></th>
                        </tr>
                    </thead>
                    <tbody id="indikаторlar">
                        @isset($indicators)
                            @foreach ($indicators as $key => $indicator)
                                <tr>
                                    <td class="{{ $key < 3 ? $indicatorClass : '' }}">{{ $key + 1 }}</td>

                                    <td>
                                        @if ($type !== 'clusters')
                                            <a href="#" wire:click="openModal('{{ $indicator->feature_name }}')"
                                               class="{{ $key < 3 ? $indicatorClass : '' }}"
                                               wire:loading.class="loadingg">
                                                {{ $translates[$indicator->feature_name] ?? $indicator->feature_name }}
                                            </a>
                                        @else
                                            <a href="#" wire:click="clusterModal('{{ $indicator->indicator }}')"
                                               wire:loading.class="loadingg">
                                                {{ $translates[$indicator->indicator] ?? $indicator->indicator }}
                                            </a>
                                        @endif
                                    </td>

                                    <td class="{{ $key < 3 ? $indicatorClass : '' }}">
                                        {{ numberToWords($indicator->average) }}
                                    </td>
                                    
                                    @if ($type === 'clusters')
                                        <td>
                                            {{ numberToWords($indicator->clusterAverage) }}
                                        </td>
                                    @endif
                                    @if ($type === 'clusters')
                                        <td class="{{ $indicator->clusterAverage < $indicator->value ? "highlightGreen" : 'highlightRed' }}">
                                            {{ numberToWords($indicator->value) }}
                                        </td>                                        
                                    @else
                                        <td class="{{ $key < 3 ? $indicatorClass : '' }}">
                                            {{ numberToWords($indicator->value) }}
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endisset
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
