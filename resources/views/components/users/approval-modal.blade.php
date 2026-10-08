@props(['user', 'roles'])

<div class="modal fade"
    id="approve-user-{{ $user->id }}"
    tabindex="-1"
    aria-labelledby="approve-title-{{ $user->id }}"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content"
            method="POST"
            action="{{ route('users.update', $user) }}">
            @csrf
            @method('PATCH')

            <div class="modal-header">
                <h2 class="modal-title fs-5"
                    id="approve-title-{{ $user->id }}">
                    {{ __('Approve') }} {{ $user->name }}
                </h2>
                <button class="btn-close" type="button"
                    data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>

            <div class="modal-body">
                <label class="form-label" for="role-{{ $user->id }}">
                    {{ __('Assign role') }}
                </label>
                <select class="form-select"
                    id="role-{{ $user->id }}"
                    name="role_id" required>
                    <x-users.role-options :roles="$roles" :selected="$user->role_id" />
                </select>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button"
                    data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>
                <button class="btn btn-success" type="submit">
                    {{ __('Approve and assign role') }}
                </button>
            </div>
        </form>
    </div>
</div>
