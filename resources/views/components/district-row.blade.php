<div class="district-row {{ $district->district_code == $active_tum ? 'active-row' : '' }}"
     wire:click="$dispatch('regionClicked', { tuman: '{{ $district->district_code }}' })">
    <span class="rank-num">{{ $index + 1 }}</span>
    <a href="#" id="{{ $district->district_code }}"
       class="district_label {{ $district->district_code == $active_tum ? 'active-district' : '' }}"
       wire:click.prevent="$dispatch('regionClicked', { tuman: '{{ $district->district_code }}' })">
        {{ $district->district->name ?? $district->district_name }}
    </a>
    <div class="progress-wrap">
        @include('partials.progress-bar', ['district' => $district, 'type' => $type, 'top_districts' => $top_districts])
    </div>
</div>
