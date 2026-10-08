@props(['options'])

@php
    $total = $options->sum('count');
    $colors = ['#198754', '#3979b7', '#d59224', '#9659a6', '#d35e53', '#338f98'];
    $angle = -M_PI / 2;
@endphp

<div class="results-chart">
    @if ($total > 0)
    <svg class="results-pie" viewBox="0 0 100 100" role="img" aria-label="{{ __('Answer distribution. Counts and percentages are listed alongside the chart.') }}">
        @foreach ($options as $option)
        @php
            $fraction = $option['count'] / $total;
            $nextAngle = $angle + $fraction * 2 * M_PI;
            $color = $colors[$loop->index % count($colors)];
            $path = sprintf('M 50 50 L %.5f %.5f A 48 48 0 %d 1 %.5f %.5f Z',
                50 + 48 * cos($angle), 50 + 48 * sin($angle), $fraction > 0.5 ? 1 : 0,
                50 + 48 * cos($nextAngle), 50 + 48 * sin($nextAngle));
            $angle = $nextAngle;
        @endphp
        @if ($fraction === 1.0 || $fraction === 1)
        <circle cx="50" cy="50" r="48" fill="{{ $color }}"><title>{{ $option['text'] }}: {{ $option['count'] }} (100%)</title></circle>
        @elseif ($fraction > 0)
        <path d="{{ $path }}" fill="{{ $color }}" stroke="white" stroke-width="0.6"><title>{{ $option['text'] }}: {{ $option['count'] }} ({{ round($fraction * 100, 1) }}%)</title></path>
        @endif
        @endforeach
    </svg>
    @else
    <div class="results-pie results-pie-empty small text-body-secondary">{{ __('No answers yet') }}</div>
    @endif

    <ul class="list-unstyled results-legend mb-0">
        @foreach ($options as $option)
        <li>
            <span class="results-swatch" style="background-color: {{ $colors[$loop->index % count($colors)] }}" aria-hidden="true"></span>
            <span class="text-break">{{ $option['text'] }} <strong>{{ $option['count'] }}</strong> <span class="text-body-secondary">({{ $total ? round($option['count'] / $total * 100, 1) : 0 }}%)</span></span>
        </li>
        @endforeach
    </ul>
</div>
<p class="small text-body-secondary mb-0 mt-2">{{ $total }} {{ $total === 1 ? __('answer') : __('answers') }}</p>
