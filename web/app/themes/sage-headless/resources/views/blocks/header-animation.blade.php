<div class="{{ $block->classes }}" style="{{ $block->inlineStyle }}">
    @if ($statements)
        @foreach ($statements as $statement)
            <div class="py-5 bg-{{ $statement['bgcolor'] }}">
                <h1 class="text-center w-full text-4xl text-{{ $statement['textcolor'] }}">{{ $statement['statement'] }}</h1>
            </div>
        @endforeach
    @else
        <p>{{ $block->preview ? 'Add a statement...' : 'No items found!' }}</p>
    @endif
</div>
