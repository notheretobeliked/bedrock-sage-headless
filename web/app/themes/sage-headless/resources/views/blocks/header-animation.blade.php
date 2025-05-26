<div class="{{ $block->classes }}">
    @if ($statements)
        @php
            $containerId = 'statement-container-' . uniqid();
        @endphp
        <div class="w-full h-[30vh] md:h-[30vh]">
            <div class="flex w-full relative flex-col h-full" id="{{ $containerId }}">
                @foreach ($statements as $index => $statement)
                    <div class="flex absolute w-full h-full inset-0 !self-center bg-{{ $statement['bgcolor'] }} opacity-0 transition-opacity duration-500"
                        data-statement-index="{{ $index }}" style="{{ $index === 0 ? 'opacity: 1;' : '' }}">
                        <p
                            class="!font-anton !fluid-text-lg !uppercase !text-center flex items-center justify-center w-full h-full text-{{ $statement['textcolor'] }}">
                            {{ $statement['statement'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        <script>
            (function() {
                const initializeAnimation = (containerId) => {
                    const container = document.getElementById(containerId);
                    if (!container) return null;

                    const statements = container.querySelectorAll('[data-statement-index]');
                    if (!statements.length) return null;

                    let currentIndex = 0;
                    let interval;

                    const showStatement = (index) => {
                        statements.forEach((statement, i) => {
                            statement.style.opacity = i === index ? '1' : '0';
                        });
                    };

                    const nextStatement = () => {
                        currentIndex = (currentIndex + 1) % statements.length;
                        showStatement(currentIndex);
                    };

                    const startInterval = () => {
                        if (interval) clearInterval(interval);
                        interval = setInterval(nextStatement, 3000);
                    };

                    const cleanup = () => {
                        if (interval) {
                            clearInterval(interval);
                            interval = null;
                        }
                    };

                    startInterval();

                    return cleanup;
                };

                const containerId = '{{ $containerId }}';
                let cleanup = null;

                // Handle both frontend and Gutenberg initialization
                if (window.wp && window.wp.blocks) {
                    // We're in Gutenberg
                    wp.domReady(() => {
                        // Initial load
                        cleanup = initializeAnimation(containerId);

                        // Handle block updates
                        wp.data.subscribe(() => {
                            const container = document.getElementById(containerId);
                            if (container && !cleanup) {
                                cleanup = initializeAnimation(containerId);
                            } else if (!container && cleanup) {
                                cleanup();
                                cleanup = null;
                            }
                        });
                    });
                } else {
                    // We're on the frontend
                    document.addEventListener('DOMContentLoaded', () => {
                        cleanup = initializeAnimation(containerId);
                    });
                }

                // Cleanup on page unload
                window.addEventListener('unload', () => {
                    if (cleanup) cleanup();
                });
            })();
        </script>
    @else
        <p>{{ $block->preview ? 'Add a statement...' : 'No items found!' }}</p>
    @endif
</div>
