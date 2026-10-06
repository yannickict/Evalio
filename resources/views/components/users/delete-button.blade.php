@props(['user', 'pending' => false])


<form method="POST" action="{{ route('users.destroy', $user) }}"
    onsubmit="return confirm('{{ $pending ? 'Permanently delete this pending registration?' : 'Permanently delete this user?' }}')">
    @csrf
    @method('DELETE')
    <button class="btn btn-outline-danger btn-sm rounded-3 px-3" type="submit"
        aria-label="Delete {{ $user->name }}" @disabled(!$pending && $user->id === auth()->id())>Delete</button>
</form>
