@props(['id', 'title' => null])
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true" {{ $attributes }}>
    <div class="modal-dialog">
        <div class="modal-content">
            @if($title || isset($header))
                <div class="modal-header">
                    @if(isset($header))
                        {{ $header }}
                    @else
                        <h5 class="modal-title" id="{{ $id }}Label">{{ $title }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    @endif
                </div>
            @endif
            <div class="modal-body">
                {{ $slot }}
            </div>
            @if(isset($footer))
                <div class="modal-footer">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
