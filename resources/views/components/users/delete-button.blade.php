@props(['user', 'pending' => false])


<form method="POST"
    action="{{ route('users.destroy', $user) }}"
    data-confirm="{{ $pending ? __('Permanently delete this pending registration?') : __('Permanently delete this user?') }}"
    onsubmit="return confirm(this.dataset.confirm)">
    @csrf
    @method('DELETE')
    <button class="btn btn-outline-danger btn-sm rounded-3 px-3" type="submit"
        aria-label="{{ __('Delete :value1', ['value1' => $user->name]) }}" @disabled(!$pending && $user->id === auth()->id())>{{ __('Delete') }}</button>
</form>
