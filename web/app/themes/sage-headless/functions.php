<?php

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our theme. We will simply require it into the script here so that we
| don't have to worry about manually loading any of our classes later on.
|
*/

if (! file_exists($composer = __DIR__.'/vendor/autoload.php')) {
    wp_die(__('Error locating autoloader. Please run <code>composer install</code>.', 'sage'));
}

require $composer;

/*
|--------------------------------------------------------------------------
| Register The Bootloader
|--------------------------------------------------------------------------
|
| The first thing we will do is schedule a new Acorn application instance
| to boot when WordPress is finished loading the theme. The application
| serves as the "glue" for all the components of Laravel and is
| the IoC container for the system binding all of the various parts.
|
*/

if (! function_exists('\Roots\bootloader')) {
    wp_die(
        __('You need to install Acorn to use this theme.', 'sage'),
        '',
        [
            'link_url' => 'https://roots.io/acorn/docs/installation/',
            'link_text' => __('Acorn Docs: Installation', 'sage'),
        ]
    );
}

\Roots\bootloader()->boot();

// Register custom GraphQL field for ticker number
add_action('graphql_register_types', function () {
    register_graphql_field('AcfTickerNumber_TickerNumber', 'currentCount', [
        'type' => 'Int',
        'description' => 'The current calculated ticker count including increment',
        'resolve' => function ($root, $args, $context, $info) {
            // Get the block data from the root
            $block_data = $root;
            
            // Get increment_by value from the ACF field data
            $increment_by = isset($block_data['incrementBy']) ? (int) $block_data['incrementBy'] : 0;
            
            // Get the base count from WordPress options
            $base_count = (int) get_option('an_subscriber_count', 0);
            
            // Return the calculated total
            return $base_count + $increment_by;
        }
    ]);
});

// Also add a field for the last updated timestamp
add_action('graphql_register_types', function () {
    register_graphql_field('AcfTickerNumber_TickerNumber', 'lastUpdated', [
        'type' => 'String',
        'description' => 'When the subscriber count was last updated',
        'resolve' => function ($root, $args, $context, $info) {
            $timestamp = get_option('an_subscriber_count_timestamp', '');
            
            if ($timestamp) {
                return date('c', $timestamp); // ISO 8601 format
            }
            
            return null;
        }
    ]);
});

/*
|--------------------------------------------------------------------------
| Register Sage Theme Files
|--------------------------------------------------------------------------
|
| Out of the box, Sage ships with categorically named theme files
| containing common functionality and setup to be bootstrapped with your
| theme. Simply add (or remove) files from the array below to change what
| is registered alongside Sage.
|
*/

collect(['preview-integration', 'setup', 'filters'])
    ->each(function ($file) {
        if (! locate_template($file = "app/{$file}.php", true, true)) {
            wp_die(
                /* translators: %s is replaced with the relative file path */
                sprintf(__('Error locating <code>%s</code> for inclusion.', 'sage'), $file)
            );
        }
    });
