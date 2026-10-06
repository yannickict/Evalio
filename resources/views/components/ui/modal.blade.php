@props(['id', 'label' => null, 'labelledby' => null])


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
                <button class="btn btn-outline-secondary rounded-3" type="button" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
