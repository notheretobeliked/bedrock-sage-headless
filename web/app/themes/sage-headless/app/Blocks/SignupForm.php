<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use Log1x\AcfComposer\Builder;

class SignupForm extends Block
{
    /**
     * The block name.
     *
     * @var string
     */
    public $name = 'Signup Form';

    /**
     * The block description.
     *
     * @var string
     */
    public $description = 'A simple Signup Form block.';

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
        'core/heading' => ['placeholder' => 'Hello World'],
        'core/paragraph' => ['placeholder' => 'Welcome to the Signup Form block.'],
    ];

    /**
     * Data to be passed to the block before rendering.
     */
    public function with(): array
    {
        return [
            'email' => get_field("email_field"),
            'phone' => get_field("phone_field"),
            'postcode' => get_field("postcode_field"),
            'union_list' => get_field("union_list"),
            'workplace' => get_field("workplace"),
        ];
    }

    /**
     * The block field group.
     */
    public function fields(): array
    {
        $signupForm = Builder::make('signup_form');

        $signupForm
            ->addText('form_id', [
                'label' => 'AN form ID',
                'required' => 1,
                'default_value' => '1e49bee5-7886-4cc3-9ab5-b987ccce6139',
            ])
            ->addTrueFalse('email_field', [
                'ui' => true,
            ])
            ->addTrueFalse('phone_field', [
                'ui' => true,
            ])
            ->addTrueFalse('postcode_field', [
                'ui' => true,
            ])
            ->addTrueFalse('union_list', [
                'ui' => true,
            ])
            ->addTrueFalse('workplace', [
                'ui' => true,
            ]);

        return $signupForm->build();
    }


    /**
     * Assets enqueued when rendering the block.
     */
    public function assets(array $block): void
    {
        //
    }
}
