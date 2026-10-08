@props(['user', 'pending' => false])


<div class="d-flex align-items-start gap-3">
    <span class="user-avatar d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success-emphasis fw-bold lh-1 flex-shrink-0" aria-hidden="true">
        {{ mb_strtoupper(mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1)) }}
    </span>
    <div class="text-break">
        <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
            <h3 class="h6 fw-semibold mb-0">{{ $user->name }}</h3>
            @if ($pending)
                <span class="badge rounded-pill text-bg-light border fw-normal">{{ __('Pending') }}</span>
            @elseif ($user->id === auth()->id())
                <span class="badge rounded-pill text-bg-light border fw-normal">{{ __('You') }}</span>
            @endif
        </div>
        <p class="small text-body-secondary mb-1">{{ $user->email }}</p>
        <p class="small text-body-secondary mb-0">{{ __('Joined') }} {{ $user->created_at->locale(app()->getLocale())->translatedFormat(app()->getLocale() === 'de' ? 'd.m.Y' : 'M j, Y') }}</p>
    </div>
</div>
