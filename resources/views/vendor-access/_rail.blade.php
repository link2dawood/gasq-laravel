{{-- The ten-stage rail. Shows where the vendor is and what is still locked. --}}
@php use App\Support\VendorEngagement as Flow; @endphp
<div class="va-rail" aria-label="Vendor access progress">
  @foreach(Flow::stages() as $key => $meta)
    @php
      $record = $engagement->stageRecord($key);
      $state = $record?->isComplete() ? 'done' : ($key === $currentStage ? 'now' : ($record?->isOpen() ? 'open' : 'locked'));
    @endphp
    <div class="va-step {{ $state }}">
      <span class="n">{{ $loop->iteration }}</span>
      <span class="t">
        @if($state === 'locked')
          {{ $meta['label'] }}
        @else
          <a href="{{ route('vendor-access.stage', ['invitation' => $invitation, 'stage' => $key]) }}">{{ $meta['label'] }}</a>
        @endif
      </span>
    </div>
  @endforeach
</div>
