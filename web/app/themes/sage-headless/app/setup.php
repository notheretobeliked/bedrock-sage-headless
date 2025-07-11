<?php

/**
 * Theme setup.
 */

namespace App;

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
    bundle('editor')->enqueue();
}, 100);

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
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
 * 
 * Hack ACF/Graphql to allow querying 'align' on 'attributes'.
 * 
 */

// Try to intercept before WPGraphQL processes the block type
add_filter('register_block_type_args', function($args, $name) {
    if (isset($args['attributes']['align'])) {
        $args['attributes']['align'] = [
            'type' => 'string',
            'default' => null,
            '__experimentalRole' => 'content',
            'source' => 'attribute',
            'selector' => '[class*="align"]',
            'extractValue' => function($value) {
                if (preg_match('/align(full|wide|left|right|center)/', $value, $matches)) {
                    return $matches[1];
                }
                return null;
            }
        ];
    }
    return $args;
}, 20, 2);



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


/**
 * Receive the AN webhook for new actions.
 *
 * @return void
 */

// Create a custom endpoint in WordPress
add_action('rest_api_init', function () {
    $result = register_rest_route('actionnetwork/v1', '/webhook', array(
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\\handle_action_network_webhook',
        'permission_callback' => '__return_true',
    ));
    
    // Add a test route for debugging
    register_rest_route('actionnetwork/v1', '/test', array(
        'methods' => 'GET',
        'callback' => function() {
            return new \WP_REST_Response('Webhook endpoint is working!', 200);
        },
        'permission_callback' => '__return_true',
    ));
    
    // Debug logging
    if ($result) {
        error_log('Action Network webhook route registered successfully');
    } else {
        error_log('Failed to register Action Network webhook route');
    }
}, 99); // Higher priority to ensure it runs after theme setup

function handle_action_network_webhook($request) {
    $data = $request->get_json_params();
    
    // Log the incoming webhook for debugging
    error_log('Action Network webhook received: ' . json_encode($data));
    
    // Verify webhook signature if Action Network provides one
    if (!verify_webhook_signature($request)) {
        return new \WP_Error('invalid_signature', 'Invalid webhook signature', array('status' => 401));
    }
    
    // Process the webhook
    if (isset($data['action_type'])) {
        switch ($data['action_type']) {
            case 'person.subscribed':
                increment_subscriber_count();
                break;
            case 'person.unsubscribed':
                decrement_subscriber_count();
                break;
            // Handle other relevant actions
        }
    }
    
    return new \WP_REST_Response('OK', 200);
}

/**
 * Counter management
 */
function increment_subscriber_count() {
    $current = get_option('an_subscriber_count', 0);
    update_option('an_subscriber_count', $current + 1);
    update_option('an_subscriber_count_timestamp', time());
    error_log('Subscriber count incremented to: ' . ($current + 1));
}

function decrement_subscriber_count() {
    $current = get_option('an_subscriber_count', 0);
    update_option('an_subscriber_count', max(0, $current - 1));
    update_option('an_subscriber_count_timestamp', time());
    error_log('Subscriber count decremented to: ' . max(0, $current - 1));
}

/**
 * Verify webhook signature from Action Network
 */
function verify_webhook_signature($request) {
    // Action Network webhook signature verification
    // Check if Action Network provides a signature header
    $signature = $request->get_header('X-Action-Network-Signature');
    
    if (!$signature) {
        // If no signature is provided, you might want to verify by other means
        // For now, we'll allow it but log it
        error_log('Action Network webhook received without signature');
        return true; // You may want to change this to false for production
    }
    
    // If Action Network provides a secret for signature verification
    $webhook_secret = defined('AN_WEBHOOK_SECRET') ? AN_WEBHOOK_SECRET : '';
    
    if (!$webhook_secret) {
        error_log('No webhook secret configured for Action Network');
        return true; // Allow for now, but should be false in production
    }
    
    // Verify the signature (implementation depends on Action Network's method)
    $body = $request->get_body();
    $calculated_signature = hash_hmac('sha256', $body, $webhook_secret);
    
    return hash_equals($signature, $calculated_signature);
}

/**
 * Fetch actual count from Action Network API for verification
 */
function fetch_actual_count_from_api() {
    $an_key = defined('AN_KEY') ? AN_KEY : '';
    
    if (!$an_key) {
        error_log('No Action Network API key configured');
        return false;
    }
    
    $total_subscribers = 0;
    $next_page_url = 'https://actionnetwork.org/api/v2/people';
    $max_pages = 100; // Safety limit to prevent infinite loops
    $page_count = 0;
    
    while ($next_page_url && $page_count < $max_pages) {
        $response = wp_remote_get($next_page_url, [
            'headers' => [
                'OSDI-API-Token' => $an_key,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            error_log('Action Network API error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 429) {
            error_log('Action Network API rate limited during verification');
            return false;
        }
        
        if ($status_code !== 200) {
            error_log('Action Network API returned status: ' . $status_code);
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['_embedded']['osdi:people'])) {
            break;
        }
        
        // Filter to only count people with subscribed email status
        $people = $data['_embedded']['osdi:people'];
        foreach ($people as $person) {
            if (isset($person['email_addresses']) && is_array($person['email_addresses'])) {
                foreach ($person['email_addresses'] as $email) {
                    if (isset($email['status']) && $email['status'] === 'subscribed') {
                        $total_subscribers++;
                        break; // Only count person once
                    }
                }
            }
        }
        
        $next_page_url = isset($data['_links']['next']['href']) ? $data['_links']['next']['href'] : null;
        $page_count++;
        
        // Add a small delay to be respectful to the API
        usleep(100000); // 0.1 seconds
    }
    
    return $total_subscribers;
}

// Daily verification cron
add_action('verify_subscriber_count', __NAMESPACE__ . '\\verify_count_with_api');

function verify_count_with_api() {
    // Only run this once per day to avoid rate limiting
    $last_verify = get_option('an_last_verify', 0);
    if (time() - $last_verify < 86400) return; // 24 hours
    
    // Fetch actual count from API (your existing logic)
    $api_count = fetch_actual_count_from_api();
    $cached_count = get_option('an_subscriber_count', 0);
    
    // If there's a significant difference, update and log
    if ($api_count !== false && abs($api_count - $cached_count) > 5) {
        update_option('an_subscriber_count', $api_count);
        error_log("Subscriber count synced: $cached_count -> $api_count");
    }
    
    update_option('an_last_verify', time());
}

/**
 * Schedule the verification cron job
 */
function schedule_subscriber_count_verification() {
    if (!wp_next_scheduled('verify_subscriber_count')) {
        wp_schedule_event(time(), 'daily', 'verify_subscriber_count');
    }
}
add_action('wp', __NAMESPACE__ . '\\schedule_subscriber_count_verification');

/**
 * Initialize subscriber count if it doesn't exist
 */
function initialize_subscriber_count() {
    if (get_option('an_subscriber_count', false) === false) {
        // Run initial count fetch
        $initial_count = fetch_actual_count_from_api();
        if ($initial_count !== false) {
            update_option('an_subscriber_count', $initial_count);
            update_option('an_subscriber_count_timestamp', time());
            error_log('Initial subscriber count set to: ' . $initial_count);
        }
    }
}
add_action('after_setup_theme', __NAMESPACE__ . '\\initialize_subscriber_count');

/**
 * Add GraphQL field for subscriber count
 */
add_action('graphql_register_types', function() {
    register_graphql_field('RootQuery', 'subscriberCount', [
        'type' => 'Int',
        'description' => 'Total number of Action Network subscribers',
        'resolve' => function() {
            return (int) get_option('an_subscriber_count', 0);
        }
    ]);
    
    register_graphql_field('RootQuery', 'subscriberCountTimestamp', [
        'type' => 'String',
        'description' => 'Last updated timestamp for subscriber count',
        'resolve' => function() {
            return get_option('an_subscriber_count_timestamp', '');
        }
    ]);
});

/**
 * Add admin interface for monitoring
 */
add_action('admin_menu', function() {
    add_options_page(
        'Action Network Stats',
        'AN Stats',
        'manage_options',
        'an-stats',
        __NAMESPACE__ . '\\render_an_stats_page'
    );
});

function render_an_stats_page() {
    $count = get_option('an_subscriber_count', 0);
    $timestamp = get_option('an_subscriber_count_timestamp', '');
    $last_verify = get_option('an_last_verify', 0);
    
    echo '<div class="wrap">';
    echo '<h1>Action Network Statistics</h1>';
    echo '<table class="form-table">';
    echo '<tr><th>Current Subscriber Count</th><td>' . number_format($count) . '</td></tr>';
    echo '<tr><th>Last Updated</th><td>' . ($timestamp ? date('Y-m-d H:i:s', $timestamp) : 'Never') . '</td></tr>';
    echo '<tr><th>Last Verification</th><td>' . ($last_verify ? date('Y-m-d H:i:s', $last_verify) : 'Never') . '</td></tr>';
    echo '</table>';
    
    if (current_user_can('manage_options')) {
        echo '<p><a href="' . admin_url('options-general.php?page=an-stats&force_verify=1') . '" class="button">Force Verification</a></p>';
        
        if (isset($_GET['force_verify'])) {
            $api_count = fetch_actual_count_from_api();
            if ($api_count !== false) {
                update_option('an_subscriber_count', $api_count);
                update_option('an_subscriber_count_timestamp', time());
                echo '<div class="notice notice-success"><p>Count updated to: ' . number_format($api_count) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Failed to fetch count from API</p></div>';
            }
        }
    }
    
    echo '</div>';
}

/**
 * Register custom GraphQL fields for ticker number block.
 */
add_action('graphql_register_types', function () {
    // Add current count field (base subscriber count only, no increment)
    register_graphql_field('AcfTickerNumber', 'currentCount', [
        'type' => 'Int',
        'description' => 'The current base subscriber count from Action Network',
        'resolve' => function ($root, $args, $context, $info) {
            // Return just the base count from WordPress options
            return (int) get_option('an_subscriber_count', 0);
        }
    ]);
    
    // Add last updated timestamp field
    register_graphql_field('AcfTickerNumber', 'lastUpdated', [
        'type' => 'String',
        'description' => 'When the subscriber count was last updated (ISO 8601 format)',
        'resolve' => function ($root, $args, $context, $info) {
            $timestamp = get_option('an_subscriber_count_timestamp', '');
            
            if ($timestamp) {
                return date('c', $timestamp); // ISO 8601 format
            }
            
            return null;
        }
    ]);
});