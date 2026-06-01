<?php
/**
 * SvelteKit Preview Integration - Token-Based Authentication
 */

namespace App\Preview;

add_post_type_support('page', 'revisions');

/**
 * Get frontend URLs based on FRONTEND_HOST environment variable
 */
function get_frontend_urls() {
    // Get FRONTEND_HOST from environment (set in .env)
    $frontend_host = env('FRONTEND_HOST') ?: 'http://localhost:5173';

    // Remove trailing slash if present
    $frontend_host = rtrim($frontend_host, '/');

    return [
        'frontend' => $frontend_host,
        'allowed_origins' => [$frontend_host]
    ];
}

/**
 * Generate a preview token for authenticated users
 */
function generate_preview_token($post_id = null) {
    if (!is_user_logged_in()) {
        return false;
    }
    
    $user = wp_get_current_user();
    
    // Check if user can edit posts/pages
    if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
        return false;
    }
    
    // Generate a short, random token
    $token = wp_generate_password(32, false);
    
    // Store the actual data in the transient
    $token_data = array(
        'user_id' => $user->ID,
        'post_id' => $post_id,
        'timestamp' => time(),
        'can_edit_posts' => current_user_can('edit_posts'),
        'can_edit_pages' => current_user_can('edit_pages')
    );
    
    // Store token in transient for validation (expires in 1 hour)
    set_transient('preview_token_' . $token, $token_data, HOUR_IN_SECONDS);
    
    return $token;
}

/**
 * Check if user is authenticated via preview token
 */
function is_preview_authenticated() {
    // Check regular WordPress authentication first
    if (is_user_logged_in() && (current_user_can('edit_posts') || current_user_can('edit_pages'))) {
        return true;
    }
    
    // Check for preview token
    $token = null;
    
    // Check X-Preview-Token header
    $token = $_SERVER['HTTP_X_PREVIEW_TOKEN'] ?? '';
    
    // Check query parameter
    if (!$token) {
        $token = $_GET['token'] ?? '';
    }
    
    if (!$token) {
        return false;
    }
    
    // Validate token
    $token_data = get_transient('preview_token_' . $token);
    
    if (!$token_data) {
        return false;
    }
    
    // Check if user still exists and has permissions
    $user = get_user_by('ID', $token_data['user_id']);
    if (!$user) {
        return false;
    }
    
    // Check stored permissions (more reliable than checking current user permissions)
    return $token_data['can_edit_posts'] || $token_data['can_edit_pages'];
}

/**
 * Enable CORS for SvelteKit frontend
 */
add_action('init', function() {
    // Skip if this is a REST API request (handled by rest_api_init hook)
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }

    $urls = get_frontend_urls();
    $allowed_origins = $urls['allowed_origins'];

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: $origin", true);
        header("Access-Control-Allow-Credentials: true", true);
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce, X-Requested-With, X-Preview-Token", true);
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE", true);

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            status_header(200);
            exit();
        }
    }
});

/**
 * Additional CORS handling for REST API endpoints
 */
add_action('rest_api_init', function() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        $urls = get_frontend_urls();
        $allowed_origins = $urls['allowed_origins'];
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Always remove any existing CORS headers first
        header_remove('Access-Control-Allow-Origin');
        header_remove('Access-Control-Allow-Credentials');
        header_remove('Access-Control-Allow-Headers');
        header_remove('Access-Control-Allow-Methods');

        if (in_array($origin, $allowed_origins)) {
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce, X-Requested-With, X-Preview-Token");
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        }

        return $value;
    }, 999);
});

/**
 * Modify preview links to include tokens
 */
add_filter('preview_post_link', function($preview_link, $post) {
    if (!is_user_logged_in()) {
        return $preview_link;
    }

    $urls = get_frontend_urls();
    $frontend_url = $urls['frontend'];

    // Generate preview token
    $token = generate_preview_token($post->ID);

    if (!$token) {
        error_log('preview_post_link: token generation failed');
        return $preview_link;
    }
    
    // Create preview URL with token - routes to /preview in SvelteKit
    switch ($post->post_type) {
        case 'post':
            return $frontend_url . '/preview?p=' . $post->ID . '&token=' . $token;
        case 'page':
            return $frontend_url . '/preview?page_id=' . $post->ID . '&token=' . $token;
        default:
            return $frontend_url . '/preview?p=' . $post->ID . '&post_type=' . $post->post_type . '&token=' . $token;
    }
}, 10, 2);

/**
 * Customize page preview links
 */
add_filter('page_link', function($link, $post_id, $sample) {
    if ($sample) { // This indicates it's a preview
        $urls = get_frontend_urls();
        $frontend_url = $urls['frontend'];

        $token = generate_preview_token($post_id);
        if ($token) {
            return $frontend_url . '/preview?page_id=' . $post_id . '&token=' . $token;
        }
    }
    // Redirect "View Page" links in admin to frontend
    if (is_admin() && !wp_doing_ajax()) {
        $urls = get_frontend_urls();
        return str_replace(home_url(), $urls['frontend'], $link);
    }
    return $link;
}, 10, 3);

/**
 * Handle custom post types preview links using IDs
 */
add_filter('get_sample_permalink', function($permalink, $post_id, $title, $name, $post) {
    if (is_admin()) {
        $urls = get_frontend_urls();
        $frontend_url = $urls['frontend'];

        $token = generate_preview_token($post_id);
        if ($token && isset($permalink[0])) {
            $preview_url = $frontend_url . '/preview?';
            if ($post->post_type === 'page') {
                $preview_url .= 'page_id=' . $post_id;
            } else {
                $preview_url .= 'p=' . $post_id;
                if ($post->post_type !== 'post') {
                    $preview_url .= '&post_type=' . $post->post_type;
                }
            }
            $preview_url .= '&token=' . $token;
            $permalink[0] = $preview_url;
        }
    }
    return $permalink;
}, 10, 5);

/**
 * REST API endpoint to validate tokens (for GraphQL authentication)
 */
add_action('rest_api_init', function() {
    register_rest_route('sveltekit/v1', '/validate-token', array(
        'methods' => 'POST',
        'callback' => function($request) {
            $token = $request->get_header('X-Preview-Token');
            
            if (!$token) {
                return new \WP_Error('no_token', 'No preview token provided', array('status' => 401));
            }
            
            // Get token data from transient
            $token_data = get_transient('preview_token_' . $token);
            
            if (!$token_data) {
                return new \WP_Error('invalid_token', 'Invalid or expired preview token', array('status' => 401));
            }
            
            // Validate token (check if it's not too old, user still exists, etc.)
            $user = get_user_by('ID', $token_data['user_id']);
            if (!$user) {
                return new \WP_Error('invalid_user', 'Token user no longer exists', array('status' => 401));
            }
            
            return array(
                'valid' => true,
                'user_id' => $token_data['user_id'],
                'post_id' => $token_data['post_id'],
                'capabilities' => $token_data['can_edit_posts'] || $token_data['can_edit_pages']
            );
        },
        'permission_callback' => '__return_true'
    ));
});

/**
 * Early authentication via determine_current_user filter
 * This runs before most WordPress authentication checks
 * Priority 20 is after default authentication but before most plugins
 */
add_filter('determine_current_user', function($user_id) {
    // Only process if no user is logged in yet
    if ($user_id) {
        return $user_id;
    }

    $token = get_preview_token_from_request();

    if ($token) {
        $token_data = get_transient('preview_token_' . $token);

        if ($token_data && isset($token_data['user_id'])) {
            return $token_data['user_id'];
        }
    }

    return $user_id;
}, 20);

/**
 * Helper function to extract preview token from request
 */
function get_preview_token_from_request() {
    $token = null;

    // Check for token in headers (case-insensitive)
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        // Headers can be case-insensitive, check common variations
        $token = $headers['X-Preview-Token'] ?? $headers['x-preview-token'] ?? $headers['X-PREVIEW-TOKEN'] ?? null;
    }

    // Fallback to $_SERVER (Apache/nginx normalize to HTTP_X_PREVIEW_TOKEN)
    if (!$token && isset($_SERVER['HTTP_X_PREVIEW_TOKEN'])) {
        $token = $_SERVER['HTTP_X_PREVIEW_TOKEN'];
    }

    // Check query parameter
    if (!$token && isset($_GET['token'])) {
        $token = $_GET['token'];
    }

    return $token;
}

/**
 * Add GraphQL authentication hook to validate preview tokens
 */
add_action('graphql_authenticate', function($user, $request) {
    $token = null;
    
    // Check for token in headers
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $token = $headers['X-Preview-Token'] ?? null;
    }
    
    // Fallback to $_SERVER
    if (!$token) {
        $token = $_SERVER['HTTP_X_PREVIEW_TOKEN'] ?? null;
    }
    
    // Check query parameter for GraphQL requests
    if (!$token) {
        $token = $_GET['token'] ?? null;
    }
    
    if ($token) {
        // Validate token
        $token_data = get_transient('preview_token_' . $token);
        
        if ($token_data) {
            $authenticated_user = get_user_by('ID', $token_data['user_id']);
            if ($authenticated_user) {
                // Set the current user for the entire request
                wp_set_current_user($authenticated_user->ID);
                
                // Also set it in the global context
                global $current_user;
                $current_user = $authenticated_user;
                
                return $authenticated_user;
            }
        }
    }
    
    return $user;
}, 10, 2);

/**
 * Additional hook to ensure user is set early in GraphQL context
 */
add_action('graphql_init', function() {
    $token = null;
    
    // Check for token in headers
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $token = $headers['X-Preview-Token'] ?? null;
    }
    
    // Fallback to $_SERVER
    if (!$token) {
        $token = $_SERVER['HTTP_X_PREVIEW_TOKEN'] ?? null;
    }
    
    // Check query parameter for GraphQL requests
    if (!$token) {
        $token = $_GET['token'] ?? null;
    }
    
    if ($token) {
        // Validate token
        $token_data = get_transient('preview_token_' . $token);
        
        if ($token_data) {
            $authenticated_user = get_user_by('ID', $token_data['user_id']);
            if ($authenticated_user) {
                // Set the current user for the entire request
                wp_set_current_user($authenticated_user->ID);
                
                // Also set it in the global context
                global $current_user;
                $current_user = $authenticated_user;
            }
        }
    }
});

/**
 * Modify GraphQL queries to include draft/private posts for authenticated users
 */
add_filter('graphql_connection_query_args', function($query_args, ...$args) {
    if (!is_preview_authenticated()) {
        return $query_args;
    }

    $query_args['post_status'] = ['publish', 'private', 'draft', 'pending', 'future'];
    return $query_args;
}, 10, 1);

/**
 * Allow access to draft/private content in single node queries for authenticated users
 */
add_filter('graphql_pre_resolve_field', function($result, $source, $args, $context, $info) {
    // Check if this is a nodeByUri query and user can edit content
    if ($info->fieldName === 'nodeByUri' && is_preview_authenticated()) {
        // Temporarily modify the query to include non-published content
        add_filter('posts_where', function($where) {
            global $wpdb;
            
            // Replace the default published-only restriction
            $where = str_replace(
                "AND {$wpdb->posts}.post_status = 'publish'",
                "AND {$wpdb->posts}.post_status IN ('publish', 'private', 'draft', 'pending', 'future')",
                $where
            );
            
            return $where;
        }, 999);
    }
    return $result;
}, 10, 5);

/**
 * Allow revisions to be accessed via GraphQL for preview-authenticated users.
 * WPGraphQL's native asPreview loads the revision post, but the Post model's
 * is_private() check may fail if token-based auth capabilities aren't fully
 * initialized. This filter bypasses that check for revisions.
 */
add_filter('graphql_pre_model_data_is_private', function($is_private, $model_name, $data) {
    if ('PostObject' === $model_name && is_preview_authenticated()) {
        if (in_array($data->post_type, ['revision', 'attachment'], true)) {
            return false;
        }
    }
    return $is_private;
}, 10, 3);

/**
 * Fix ACF single image fields returning null in preview context.
 *
 * WPGraphQL's sanitize_post_stati() strips non-publish statuses when the
 * current user can't edit_posts. Token-based preview auth doesn't set a
 * real WordPress user (user remains 0), so attachments with 'inherit'
 * status get filtered out. The Gallery resolver bypasses this by calling
 * set_query_arg('post_status','any') after sanitization, but the Image
 * resolver doesn't. This filter runs after sanitization and restores
 * 'any' status for attachment queries during preview.
 */
add_filter('graphql_connection_query_args', function($query_args) {
    if (
        is_preview_authenticated()
        && isset($query_args['post_type'])
        && $query_args['post_type'] === 'attachment'
    ) {
        $query_args['post_status'] = 'any';
    }
    return $query_args;
}, 10, 5);

/**
 * Redirect public (non-admin, non-API) requests to the frontend.
 */
add_action('template_redirect', function() {
    // Don't redirect admin, AJAX, REST API, GraphQL, or cron requests
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }
    // Don't redirect GraphQL or REST API endpoints
    if (strpos($_SERVER['REQUEST_URI'], '/graphql') !== false) {
        return;
    }
    if (strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false) {
        return;
    }
    // Don't redirect robots.txt, sitemaps, or feeds
    if (is_robots() || is_feed()) {
        return;
    }

    $urls = get_frontend_urls();
    $frontend_url = $urls['frontend'];
    $path = wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

    // Strip the /wp prefix if present (Bedrock structure)
    $wp_base = wp_parse_url(home_url(), PHP_URL_PATH) ?: '';
    if ($wp_base && strpos($path, $wp_base) === 0) {
        $path = substr($path, strlen($wp_base)) ?: '/';
    }

    wp_redirect($frontend_url . $path, 301);
    exit;
});

/**
 * Modify "View" links for custom post types in admin to point to frontend
 */
add_filter('post_type_link', function($permalink, $post) {
    if (is_admin() && !wp_doing_ajax()) {
        $urls = get_frontend_urls();
        return str_replace(home_url(), $urls['frontend'], $permalink);
    }
    return $permalink;
}, 10, 2);

/**
 * Add a custom REST endpoint for auth checking (optional, for debugging)
 */
add_action('rest_api_init', function() {
    // Simple test endpoint to verify REST API is working
    register_rest_route('sveltekit/v1', '/test', [
        'methods' => 'GET',
        'callback' => function($request) {
            return [
                'message' => 'SvelteKit REST API is working!',
                'timestamp' => current_time('mysql'),
                'wp_env' => defined('WP_ENV') ? WP_ENV : 'undefined'
            ];
        },
        'permission_callback' => '__return_true'
    ]);

    // Debug endpoint to check token and post
    register_rest_route('sveltekit/v1', '/debug-preview/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => function($request) {
            $post_id = $request['id'];
            $token = $request->get_param('token') ?: $_GET['token'] ?? '';
            
            // Check if post exists
            $post = get_post($post_id);
            
            // Validate token if provided
            $token_valid = false;
            $token_data = null;
            $transient_key = '';
            if ($token) {
                $transient_key = 'preview_token_' . $token;
                $token_data = get_transient($transient_key);
                $token_valid = $token_data !== false;
            }
            
            // Check authentication
            $is_authenticated = is_preview_authenticated();
            
            // Get all transients that start with preview_token_
            global $wpdb;
            $transients = $wpdb->get_results(
                "SELECT option_name, option_value FROM {$wpdb->options} 
                 WHERE option_name LIKE '_transient_preview_token_%' 
                 ORDER BY option_name"
            );
            
            return [
                'post_id' => $post_id,
                'post_exists' => $post !== null,
                'post_data' => $post ? [
                    'ID' => $post->ID,
                    'post_title' => $post->post_title,
                    'post_status' => $post->post_status,
                    'post_type' => $post->post_type,
                    'post_name' => $post->post_name,
                    'post_modified' => $post->post_modified,
                ] : null,
                'token_provided' => !empty($token),
                'token_length' => strlen($token),
                'token_preview' => substr($token, 0, 10) . '...',
                'transient_key' => $transient_key,
                'token_valid' => $token_valid,
                'token_data' => $token_data,
                'is_preview_authenticated' => $is_authenticated,
                'user_logged_in' => is_user_logged_in(),
                'current_user_id' => get_current_user_id(),
                'can_edit_posts' => current_user_can('edit_posts'),
                'can_edit_pages' => current_user_can('edit_pages'),
                'headers' => [
                    'X-Preview-Token' => $_SERVER['HTTP_X_PREVIEW_TOKEN'] ?? 'not_set',
                ],
                'query_params' => $_GET,
                'existing_tokens' => array_map(function($transient) {
                    return [
                        'key' => str_replace('_transient_', '', $transient->option_name),
                        'data' => maybe_unserialize($transient->option_value)
                    ];
                }, $transients),
                'timestamp' => time(),
            ];
        },
        'permission_callback' => '__return_true'
    ]);
});

/**
 * Add preview notice in WordPress admin (optional enhancement)
 */
add_action('admin_notices', function() {
    global $post;
    
    if (isset($post) && get_current_screen()->base === 'post') {
        $urls = get_frontend_urls();
        $frontend_url = $urls['frontend'];
        
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>SvelteKit Integration:</strong> ';
        echo 'Preview links will open in your frontend at <code>' . esc_html($frontend_url) . '</code></p>';
        echo '</div>';
    }
});

/**
 * Modify the "View Post" link in admin to point to frontend (optional)
 */
add_filter('post_link', function($permalink, $post) {
    // Only modify in admin context, not for public links
    if (is_admin() && !wp_doing_ajax()) {
        $urls = get_frontend_urls();
        $frontend_url = $urls['frontend'];
        
        // Replace WordPress domain with frontend domain
        $wp_url = home_url();
        return str_replace($wp_url, $frontend_url, $permalink);
    }
    return $permalink;
}, 10, 2);

/**
 * Allow GraphQL to query posts by ID regardless of status for authenticated users
 */
add_filter('graphql_post_object_connection_query_args', function($query_args, $source, $args, $context, $info) {
    if (is_preview_authenticated()) {
        $query_args['post_status'] = ['publish', 'private', 'draft', 'pending', 'future', 'inherit'];
    }
    return $query_args;
}, 10, 5);

/**
 * Add a custom GraphQL debug field to check authentication in GraphQL context
 */
add_action('graphql_register_types', function() {
    register_graphql_field('RootQuery', 'debugAuth', [
        'type' => 'String',
        'description' => 'Debug authentication status in GraphQL context',
        'resolve' => function($source, $args, $context, $info) {
            $debug_info = [
                'user_logged_in' => is_user_logged_in(),
                'current_user_id' => get_current_user_id(),
                'can_edit_posts' => current_user_can('edit_posts'),
                'can_edit_pages' => current_user_can('edit_pages'),
                'is_preview_authenticated' => is_preview_authenticated(),
                'token_in_query' => isset($_GET['token']) ? 'yes' : 'no',
                'token_in_header' => isset($_SERVER['HTTP_X_PREVIEW_TOKEN']) ? 'yes' : 'no',
            ];
            
            return json_encode($debug_info, JSON_PRETTY_PRINT);
        }
    ]);
});