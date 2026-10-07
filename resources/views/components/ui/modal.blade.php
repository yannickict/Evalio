@props(['id', 'label' => null, 'labelledby' => null, 'editPermission' => null, 'editUrl' => null, 'editLabel' => 'Edit', 'deletePermission' => null, 'deleteUrl' => null, 'deleteConfirmation' => 'Permanently delete this item? This cannot be undone.'])


<div class="modal fade" id="{{ $id }}" tabindex="-1"
    @if ($labelledby) aria-labelledby="{{ $labelledby }}" @else aria-label="{{ $label }}" @endif
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header px-4 py-3">
                {{ $header ?? '' }}
                <button class="btn-close ms-auto" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">{{ $slot }}</div>
            <div class="modal-footer px-4 py-3">
                @if ($editPermission)
                @can($editPermission)
                @if ($editUrl)
                <a class="btn btn-success rounded-3" href="{{ $editUrl }}">{{ $editLabel }}</a>
                @else
                <button class="btn btn-success rounded-3" type="button">{{ $editLabel }}</button>
                @endif
                @endcan
                @endif
                @if ($deletePermission)
                @can($deletePermission)
                @if ($deleteUrl)
                <form method="POST" action="{{ $deleteUrl }}" data-confirm="{{ $deleteConfirmation }}"
                    onsubmit="return confirm(this.dataset.confirm)">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger rounded-3" type="submit">Delete</button>
                </form>
                @else
                <button class="btn btn-outline-danger rounded-3" type="button">Delete</button>
                @endif
                @endcan
                @endif
                <button class="btn btn-outline-secondary rounded-3" type="button" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
