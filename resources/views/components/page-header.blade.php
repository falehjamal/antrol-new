@props(['title', 'subtitle' => null])

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="page-header-title">{{ $title }}</h4>
        @if ($subtitle)
            <p class="page-header-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="d-flex gap-2 flex-wrap align-items-center">
            {{ $actions }}
        </div>
    @endif
</div>
