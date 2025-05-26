<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use Log1x\AcfComposer\Builder;

class HomeSection extends Block
{
    /**
     * The block name.
     *
     * @var string
     */
    public $name = 'Home Section';

    /**
     * The block description.
     *
     * @var string
     */
    public $description = 'A simple Home Section block.';

    /**
     * The block category.
     *
     * @var string
     */
    public $category = 'formatting';

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
    public $post_types = [];

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
    public $align = 'fill';

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
        'mode' => false,
        'multiple' => true,
        'jsx' => true,
        'color' => [
            'background' => true,
            'text' => true,
            'gradient' => true,
        ],
    ];

    /**
     * The block template.
     *
     * @var array
     */
    public $template = [
        'core/heading' => ['placeholder' => 'Heat strike', 'level' => 1],
        'core/paragraph' => ['placeholder' => 'Enter type and other blocks here.'],
    ];

    /**
     * Data to be passed to the block before rendering.
     */
    public function with(): array
    {
        return [
            'button_label' => get_field('accordeonlabel'),
        ];
    }

    /**
     * The block field group.
     */
    public function fields(): array
    {
        $homeSection = Builder::make('home_section');

        $homeSection
        ->addTrueFalse('default_active', [
            'label' => 'Active by default?',
            'instructions' => '',
            'required' => 0,
            'default_value' => 0,
            'ui' => 1
        ])
        ->addText('accordeonlabel', [
            'label' => 'Label for accordeon button',
            'instructions' => '',
            'required' => 1,
            'wrapper' => [
                'width' => '',
                'class' => '',
                'id' => '',
            ],
            'default_value' => 'Find out more',
        ])
        ;

        return $homeSection->build();
    }

    /**
     * Assets enqueued when rendering the block.
     */
    public function assets(array $block): void
    {
        wp_enqueue_style('home-section', asset('styles/blocks/home-section.css')->uri(), [], null);
    }
}
