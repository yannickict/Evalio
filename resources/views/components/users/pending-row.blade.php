@props(['user', 'roles'])

<article class="list-group-item px-3 px-md-4 py-3">
    <div class="row align-items-center g-3">
        <div class="col-12 col-md-7">
            <x-users.identity :user="$user" pending />
        </div>
        <div class="col-12 col-md-5">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-success btn-sm rounded-3 px-3 flex-grow-1"
                    type="button"
                    data-bs-toggle="modal"
                    data-bs-target="#approve-user-{{ $user->id }}">
                    Approve
                </button>

                <x-users.approval-modal :user="$user" :roles="$roles" />
                <x-users.delete-button :user="$user" pending />
            </div>
            <p class="small text-body-secondary mt-2 mb-0">Choose a role when approving.</p>
        </div>
    </div>
</article>
