@unless ($block->preview)
    <div {{ $attributes }}>
@endunless

    @if ($getCounter)
        <h1 class="text-center">{{ $getCounter }}</h1>
    @endif


@unless ($block->preview)
    </div>
@endunless
