<div class="row px-1 py-1">
    <div class="col-lg-5 user_name">
        <div class="form-check">
            <a href="#"
               id="{{ $district->district_code }}"
               class="form-check-label district_label"
               style="font-weight:{{ $district->district_code == $active_tum ? 'bold' : '' }}"
               wire:click="$emit('regionClicked', '{{ $district->district_code }}')">
                {{ $index + 1 }}. {{ $district->district->name ?? $district->district_name }}
            </a>
        </div>
    </div>
    <div class="col-lg-7 progress_indicator">
        @include('partials.progress-bar', ['district' => $district, 'type' => $type, 'top_districts' => $top_districts])
    </div>
</div>
