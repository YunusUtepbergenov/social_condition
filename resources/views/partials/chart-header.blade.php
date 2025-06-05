@php
    if ($type === 'mood') {
        $prefix = 'Истеъмолчилар кайфияти индекси';
    } elseif ($type === 'protests') {
        $prefix = 'Оммавий норозилик бўлиш эҳтимоли';
    } elseif ($type === 'clusters') {
        $prefix = 'Ҳудудлар тоифаси';
    } else {
        $prefix = $translates[$activeIndicator] ?? '';
    }
@endphp

<div class="col-sm-12">
    <h5 class="card-header timeline">
        {{ $prefix }} (
        @if($activeRegion == 'republic')
            {{ $active_tum ? findDistrict($active_tum) : 'Республика бўйича' }}
        @else
            {{ $active_tum ? findDistrict($active_tum) : findRegion($activeRegion) . ' бўйича' }}
        @endif
        )
    </h5>
</div>
