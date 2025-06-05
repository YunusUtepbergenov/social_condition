@php
    if ($district->cluster_id == 1) {
        $color = 'rgb(115, 182, 107)';
    } elseif ($district->cluster_id == 2) {
        $color = 'rgb(201, 99, 207)';
    } elseif ($district->cluster_id == 3) {
        $color = 'rgb(160, 160, 160)';
    } elseif ($district->cluster_id == 4) {
        $color = 'rgb(250, 167, 63)';
    } elseif ($district->cluster_id == 5) {
        $color = 'rgb(68, 119, 170)';
    } else {
        $color = 'gray';
    }

    $diff = (int) $district->diff;
    $iconClass = $diff > 0 ? 'badge badge-success bx bxs-up-arrow-alt ' : ($diff < 0 ? 'badge badge-danger bx bxs-down-arrow-alt' : 'd-none');
    $diffValue = $diff !== 0 ? abs($diff) : '';
@endphp

<div class="row px-1 py-1">
    <div class="col-lg-5 user_name">
        <div class="form-check">
            <a href="#" id="{{ $district->district_code }}"
               class="form-check-label district_label"
               style="font-weight: {{ $district->district_code == $active_tum ? 'bold' : 'normal' }}"
               wire:click="$emit('regionClicked', '{{ $district->district_code }}')">
                {{ $district->district->name }}
                <i class="{{ $iconClass }}">{{ $diffValue }}</i>
            </a>
        </div>
    </div>
    <div class="col-lg-7 progress_indicator">
        <div class="progress">
            <div class="progress-bar"
                 role="progressbar"
                 style="background-color: {{ $color }}; color: white; width: 100%;"
                 aria-valuemin="0"
                 aria-valuemax="100">
                {{ $cluster->name }}
            </div>
        </div>
    </div>
</div>
