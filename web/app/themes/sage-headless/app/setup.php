<?php

/**
 * Theme setup.
 */

namespace App;

use function Roots\asset;
use function Roots\bundle;

/**
 * Register the theme assets.
 *
 * @return void
 */
add_action('wp_enqueue_scripts', function () {
    bundle('app')->enqueue();
}, 100);

/**
 * Register the theme assets with the block editor.
 *
 * @return void
 */
add_action('enqueue_block_editor_assets', function () {
    bundle('editor')->enqueueJs();
}, 100);

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    /**
     * Enable editor styles.
     * Loads frontend CSS into the editor via add_editor_style()
     * so WordPress auto-scopes selectors under .editor-styles-wrapper.
     *
     * @link https://discourse.roots.io/t/sage-and-editor-styles/24371
     */
    add_theme_support('editor-styles');
    add_editor_style(asset('app.css')->relativePath(get_theme_file_path()));

    /**
     * Disable full-site editing support.
     *
     * @link https://wptavern.com/gutenberg-10-5-embeds-pdfs-adds-verse-block-color-options-and-introduces-new-patterns
     */
    remove_theme_support('block-templates');

    /**
     * Register the navigation menus.
     *
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'sage'),
    ]);

    /**
     * Disable the default block patterns.
     *
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
     */
    remove_theme_support('core-block-patterns');

    /**
     * Enable plugins to manage the document title.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Enable post thumbnail support.
     *
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    /**
     * Replace default WordPress image sizes with responsive-friendly ones.
     * Width-only with proportional height for better srcset generation.
     */
    remove_image_size('thumbnail');
    remove_image_size('medium');
    remove_image_size('medium_large');
    remove_image_size('large');

    add_image_size('thumbnail', 300, 0, false);
    add_image_size('small', 600, 0, false);
    add_image_size('medium', 900, 0, false);
    add_image_size('medium_large', 1200, 0, false);
    add_image_size('large', 1600, 0, false);
    add_image_size('x_large', 2400, 0, false);

    /**
     * Enable responsive embed support.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');
    add_theme_support( 'align-wide' );


    /**
     * Enable HTML5 markup support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
     */
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    /**
     * Enable selective refresh for widgets in customizer.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#customize-selective-refresh-widgets
     */
    add_theme_support('customize-selective-refresh-widgets');
}, 20);


/**
 * Remove the Comments menu item from the admin.
 */
add_action('admin_menu', function () {
    remove_menu_page('edit-comments.php');
});

/**
 * Restrict available Gutenberg blocks to those with frontend Svelte components.
 * Blocks not in this list won't appear in the editor.
 * Update this list when adding new block components to the frontend.
 */
add_filter('allowed_block_types_all', function () {
    return [
        // Layout
        'core/group',
        'core/columns',
        'core/column',
        'core/cover',
        'core/spacer',
        'core/buttons',
        'core/button',

        // Content
        'core/paragraph',
        'core/heading',
        'core/image',
        'core/video',
        'core/embed',
        'core/list',
        'core/list-item',
        'core/quote',
        'core/html',
        'core/footnotes',

        // Query
        'core/latest-posts',
        'core/query',
        'core/post-template',
        'core/query-no-results',
        'core/query-pagination',
        'core/query-pagination-previous',
        'core/query-pagination-numbers',
        'core/query-pagination-next',
        'core/post-title',
        'core/post-featured-image',
        'core/post-date',

        // Interactive
        'core/accordion',
        'core/accordion-item',
        'core/accordion-panel',
        'core/accordion-heading',
    ];
});

/**
 * Make custom image sizes available in the admin media picker.
 */
add_filter('image_size_names_choose', function ($sizes) {
    return array_merge($sizes, [
        'x_large' => __('Extra Large'),
    ]);
});

/**
 * Configure WebP Uploads plugin to generate both WebP and AVIF formats.
 * Requires the webp-uploads plugin (included via Composer).
 */
add_filter('webp_uploads_upload_image_mime_transforms', function ($transforms) {
    return [
        'image/jpeg' => ['image/webp', 'image/avif'],
        'image/png'  => ['image/webp', 'image/avif'],
        'image/gif'  => ['image/webp'],
    ];
});

/**
 * Register the theme sidebars.
 *
 * @return void
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ];

    register_sidebar([
        'name' => __('Primary', 'sage'),
        'id' => 'sidebar-primary',
    ] + $config);

    register_sidebar([
        'name' => __('Footer', 'sage'),
        'id' => 'sidebar-footer',
    ] + $config);
});