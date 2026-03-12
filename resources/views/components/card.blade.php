@props(['title' => null, 'subtitle' => null])
<div {{ $attributes->merge(['class' => 'card gasq-card']) }}>
    @if($title || $subtitle || isset($header))
        <div class="card-header">
            @if(isset($header))
                {{ $header }}
            @else
                @if($title)<h5 class="card-title mb-0">{{ $title }}</h5>@endif
                @if($subtitle)<p class="text-gasq-muted small mb-0 mt-1">{{ $subtitle }}</p>@endif
            @endif
        </div>
    @endif
    <div class="card-body">
        {{ $slot }}
    </div>
    @if(isset($footer))
        <div class="card-footer border-top border-gasq bg-transparent">
            {{ $footer }}
        </div>
    @endif
</div>
