@props(['id', 'headingId', 'title'])

<section {{ $attributes->class(['accordion'])->merge(['id' => $id, 'aria-labelledby' => $headingId]) }}>
    <div class="accordion-item border rounded-2 overflow-hidden">
        <h2 class="accordion-header" id="{{ $headingId }}">
            <button class="accordion-button bg-white gap-3" type="button"
                data-bs-toggle="collapse" data-bs-target="#{{ $id }}-content"
                aria-expanded="true" aria-controls="{{ $id }}-content">
                <span class="h5 fw-semibold mb-0">{{ $title }}</span>
                <span class="badge rounded-1 text-primary-emphasis bg-primary-subtle border border-primary-subtle">{{ $count }}</span>
            </button>
        </h2>
        <div id="{{ $id }}-content" class="collapse show">
            <div class="card border-0 rounded-0 rounded-bottom overflow-hidden">
                <div class="list-group list-group-flush">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</section>
