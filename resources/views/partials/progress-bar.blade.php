<div class="progress">
    @php
        $barStyle = 'transition-duration: 600ms; background-color: rgb(68, 119, 170); color: white;';
        $width = 50;
        $label = number_format(round($district->score, 1), 1, '.', ' ');
    @endphp

    @if($type === 'mood')
        @php
            $color = $district->label === 3 ? 'rgb(4, 117, 53)' : ($district->label === 2 ? 'rgb(115, 115, 115)' : 'rgb(68, 119, 170)');
            $barStyle = "background-color: $color; color: white;";
            $width = ($district->score / 200) * 100;
        @endphp
    @elseif($type === 'protests')
        @php
            $width = $district->score > 9 ? $district->score : 9;
        @endphp
    @elseif($type === 'indicator')
        @php
            $maxScore = ($top_districts->first()->score ?? 0) ?: 1;
            $width = max(($district->score / $maxScore) * 100, 25);
            $label = numberToWords($district->score);
        @endphp
    @endif

    <div class="progress-bar" role="progressbar" style="{{ $barStyle }} width:{{ $width }}%;" aria-valuemin="0" aria-valuemax="100">
        {{ $label }}
    </div>
</div>