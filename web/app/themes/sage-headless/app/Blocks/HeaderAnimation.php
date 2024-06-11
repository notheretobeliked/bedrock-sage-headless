<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use Log1x\AcfComposer\Builder;

class HeaderAnimation extends Block
{
    /**
     * The block name.
     *
     * @var string
     */
    public $name = 'Header Animation';

    /**
     * The block description.
     *
     * @var string
     */
    public $description = 'A simple Header Animation block.';

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
            'gradient' => true,
        ],
    ];

    /**
     * The block template.
     *
     * @var array
     */
    public $template = [
        'core/heading' => ['placeholder' => 'Hello World'],
        'core/paragraph' => ['placeholder' => 'Welcome to the Header Animation block.'],
    ];

    /**
     * Data to be passed to the block before rendering.
     */
    public function with(): array
    {
        return [
            'statements' => $this->statements(),
        ];
    }

    /**
     * The block field group.
     */
    public function fields(): array
    {
        $headerAnimation = Builder::make('header_animation');

        $headerAnimation
            ->addRepeater('statements', [
                'label' => 'Statements',
                'required' => 1,
                'layout' => 'block',
            ])
            ->addText('statement')
            ->addSelect('bgcolor', [
                'label' => 'Background Color',
                'choices' => [
                    'extremedanger' => 'Extreme Danger',
                    'extremecaution' => 'Extreme Caution',
                    'caution' => 'Caution',
                    'danger' => 'Danger',
                    'black' => 'Black',
                    'white' => 'White',
                ],
                'default_value' => 'caution',
            ])
            ->addSelect('textcolor', [
                'label' => 'Text Color',
                'choices' => [
                    'extremedanger' => 'Extreme Danger',
                    'extremecaution' => 'Extreme Caution',
                    'caution' => 'Caution',
                    'danger' => 'Danger',
                    'black' => 'Black',
                    'white' => 'White',
                ],
                'default_value' => 'caution',
            ])
            ->endRepeater();

        return $headerAnimation->build();
    }

    /**
     * Retrieve the items.
     *
     * @return array
     */
    public function statements()
    {
        return get_field('statements');
    }

    /**
     * Assets enqueued when rendering the block.
     */
    public function assets(array $block): void
    {
        //
    }
}
