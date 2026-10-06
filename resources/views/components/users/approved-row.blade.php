@props(['user', 'roles'])

<article class="list-group-item px-3 px-md-4 py-3">
    <div class="row align-items-center g-3">
        <div class="col-12 col-md-7">
            <x-users.identity :user="$user" />
        </div>
        <div class="col-12 col-md-5">
            <div class="d-flex align-items-end gap-3">
                <form class="flex-grow-1" method="POST" action="{{ route('users.update', $user) }}">
                    @csrf
                    @method('PATCH')
                    <label class="form-label small text-body-secondary mb-1" for="role-{{ $user->id }}">
                        Role <span class="visually-hidden">for {{ $user->name }}</span>
                    </label>
                    <select class="form-select form-select-sm bg-body-tertiary rounded-3"
                        id="role-{{ $user->id }}"
                        name="role_id" required onchange="this.form.requestSubmit()" @disabled($user->is(auth()->user()))>
                        <x-users.role-options :roles="$roles" :selected="$user->role_id" />
                    </select>
                    <noscript><button class="btn btn-success btn-sm mt-2" type="submit" @disabled($user->is(auth()->user()))>Save role</button></noscript>
                </form>
                <x-users.delete-button :user="$user" />
            </div>
            <p class="small text-body-secondary mt-2 mb-0">{{ $user->is(auth()->user()) ? 'You cannot change your own role.' : 'Role changes save automatically.' }}</p>
        </div>
    </div>
</article>
