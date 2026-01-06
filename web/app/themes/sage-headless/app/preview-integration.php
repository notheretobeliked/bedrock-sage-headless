<?php
/**
 * SvelteKit Preview Integration - Token-Based Authentication
 */

namespace App\Preview;

add_post_type_support('page', 'revisions');

/**
 * Get frontend URLs based on environment
 */
function get_frontend_urls() {
    // Get environment from WP_ENV constant (set in Bedrock)
    $env = defined('WP_ENV') ? WP_ENV : 'production';
    
    switch ($env) {
        case 'development':
            return [
                'frontend' => 'http://localhost:5173',
                'allowed_origins' => ['http://localhost:5173']
            ];
        case 'staging':
            return [
                'frontend' => 'https://staging.heatstrike.uk', // If you have staging
                'allowed_origins' => ['https://staging.heatstrike.uk']
            ];
        case 'production':
        default:
            return [
                'frontend' => 'https://heatstrike.uk',
                'allowed_origins' => ['https://heatstrike.uk']
            ];
    }
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
    $urls = get_frontend_urls();
    $allowed_origins = $urls['allowed_origins'];
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce, X-Requested-With, X-Preview-Token");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        
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
        
        if (in_array($origin, $allowed_origins)) {
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce, X-Requested-With, X-Preview-Token");
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        }
        
        return $value;
    });
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
        return $preview_link;
    }
    
    // Create preview URL with token
    switch ($post->post_type) {
        case 'post':
            return $frontend_url . '/?preview=true&p=' . $post->ID . '&token=' . $token;
        case 'page':
            return $frontend_url . '/?preview=true&page_id=' . $post->ID . '&token=' . $token;
        default:
            return $frontend_url . '/?preview=true&p=' . $post->ID . '&post_type=' . $post->post_type . '&token=' . $token;
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
            return $frontend_url . '/?preview=true&page_id=' . $post_id . '&token=' . $token;
        }
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
            $preview_url = $frontend_url . '/?preview=true&';
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
 * Handle page and post queries with asPreview parameter
 */
add_filter('graphql_pre_resolve_field', function($result, $source, $args, $context, $info) {
    // Handle page queries with asPreview
    if (($info->fieldName === 'page' || $info->fieldName === 'post') && 
        isset($args['asPreview']) && $args['asPreview'] === true &&
        is_preview_authenticated()) {
        
        // For asPreview queries, we need to modify the query to include all post statuses
        // and also handle revisions properly
        add_filter('posts_where', function($where) {
            global $wpdb;
            
            // Allow all post statuses for preview queries
            $where = preg_replace(
                "/AND {$wpdb->posts}\.post_status = '[^']*'/",
                "AND {$wpdb->posts}.post_status IN ('publish', 'private', 'draft', 'pending', 'future', 'inherit')",
                $where
            );
            
            return $where;
        }, 999);
        
        // Also modify the posts_results to handle the case where we want the latest revision
        // or the published post itself for preview
        add_filter('posts_results', function($posts, $query) {
            if (empty($posts) && isset($query->query_vars['p'])) {
                // If no results found, try to get the published post
                $post_id = $query->query_vars['p'];
                $published_post = get_post($post_id);
                if ($published_post && $published_post->post_status === 'publish') {
                    return [$published_post];
                }
            }
            return $posts;
        }, 10, 2);
    }
    
    return $result;
}, 5, 5);

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
        $query_args['post_status'] = ['publish', 'private', 'draft', 'pending', 'future'];
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