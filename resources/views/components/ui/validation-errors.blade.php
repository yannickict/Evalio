@props(['errors', 'heading' => null, 'headingClass' => 'fw-semibold mb-2'])


@if ($errors->any())
<div {{ $attributes->class(['alert alert-danger']) }} role="alert">
    @if ($heading)
    <p class="{{ $headingClass }}">{{ $heading }}</p>
    @endif
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
