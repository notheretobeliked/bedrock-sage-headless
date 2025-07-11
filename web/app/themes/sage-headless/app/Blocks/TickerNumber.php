<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use Log1x\AcfComposer\Builder;

class TickerNumber extends Block
{
    /**
     * The block name.
     *
     * @var string
     */
    public $name = 'Ticker Number';

    /**
     * The block description.
     *
     * @var string
     */
    public $description = 'Block to output the current ticker number';

    /**
     * The block category.
     *
     * @var string
     */
    public $category = 'text';

    /**
     * The block icon.
     *
     * @var string|array
     */
    public $icon = 'editor-ul';

    /**
     * The block keywords.
     *
     * @var array
     */
    public $keywords = [];

    /**
     * The block post type allow list.
     *
     * @var array
     */
    public $post_types = ['post', 'page'];

    /**
     * The parent block type allow list.
     *
     * @var array
     */
    public $parent = [];

    /**
     * The ancestor block type allow list.
     *
     * @var array
     */
    public $ancestor = [];

    /**
     * The default block mode.
     *
     * @var string
     */
    public $mode = 'preview';

    /**
     * The default block alignment.
     *
     * @var string
     */
    public $align = '';

    /**
     * The default block text alignment.
     *
     * @var string
     */
    public $align_text = '';

    /**
     * The default block content alignment.
     *
     * @var string
     */
    public $align_content = '';

    /**
     * The default block spacing.
     *
     * @var array
     */
    public $spacing = [
        'padding' => null,
        'margin' => null,
    ];

    /**
     * Example data for the block.
     *
     * @var array
     */
    public $example = [
        'counter' => 1000,
    ];

    /**
     * The supported block features.
     *
     * @var array
     */
    public $supports = [
        'align' => true,
        'align_text' => false,
        'align_content' => false,
        'full_height' => false,
        'anchor' => false,
        'mode' => true,
        'multiple' => true,
        'jsx' => true,
        'color' => [
            'background' => true,
            'text' => true,
            'gradients' => false,
        ],
        'spacing' => [
            'padding' => false,
            'margin' => false,
        ],
    ];

    /**
     * The block template.
     *
     * @var array
     */
    public $template = [
        'core/heading' => ['placeholder' => 'Hello World'],
        'core/paragraph' => ['placeholder' => 'Welcome to the Ticker Number block.'],
    ];

    /**
     * Data to be passed to the block before rendering.
     */
    public function with(): array
    {
        return [
            'getCounter' => $this->getCounter(),
            'formattedCounter' => number_format($this->getCounter()),
            'lastUpdated' => $this->getLastUpdated(),
        ];
    }

    /**
     * The block field group.
     */
    public function fields(): array
    {
        $fields = Builder::make('ticker_number');

        $fields
            ->addNumber('increment_by', [
                'label' => 'Increment number',
                'instructions' => 'Add this number to the current subscriber count',
                'required' => 0,
                'default_value' => 0,
                'min' => 0,
            ]);

        return $fields->build();
    }

    /**
     * Retrieve the counter value.
     *
     * @return int
     */
    public function getCounter(): int
    {
        // Get the increment value from ACF field, default to 0 if not set
        $increment_by = (int) get_field('increment_by') ?: 0;
        
        // Get the current subscriber count from WordPress options (set by webhook)
        $base_count = (int) get_option('an_subscriber_count', 0);
        
        // Add increment to base count
        $total = $base_count + $increment_by;
        
        // Log for debugging
        error_log("TickerNumber: base_count={$base_count}, increment_by={$increment_by}, total={$total}");
        
        return $total;
    }

    /**
     * Get the last updated timestamp.
     *
     * @return string
     */
    public function getLastUpdated(): string
    {
        $timestamp = get_option('an_subscriber_count_timestamp', '');
        
        if ($timestamp) {
            return date('Y-m-d H:i:s', $timestamp);
        }
        
        return 'Never';
    }

    /**
     * Assets enqueued with 'enqueue_block_assets' when rendering the block.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/enqueueing-assets-in-the-editor/#editor-content-scripts-and-styles
     */
    public function assets(array $block): void
    {
        //
    }
}