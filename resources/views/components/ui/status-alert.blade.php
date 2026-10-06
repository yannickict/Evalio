@if (session('status'))
<div {{ $attributes->class(['alert alert-success']) }} role="status" aria-live="polite">
    {{ session('status') }}
</div>
@endif
