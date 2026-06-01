<?php

/**
 * Block configuration.
 *
 * Align attribute fix, GraphQL nullability fixes,
 * and server-side resolved post data for query blocks.
 */

namespace App;

/**
 * Override block type registration arguments.
 *
 * Fixes ACF/GraphQL align attribute querying for core blocks.
 * ACF blocks are skipped to preserve their own align defaults.
 */
add_filter('register_block_type_args', function ($args, $name) {
    // Fix align attribute for GraphQL querying (core blocks only — ACF blocks
    // handle align via block comments and need their defaults preserved)
    if (isset($args['attributes']['align']) && !str_starts_with($name, 'acf/')) {
        $args['attributes']['align'] = [
            'type' => 'string',
            'default' => null,
            '__experimentalRole' => 'content',
            'source' => 'attribute',
            'selector' => '[class*="align"]',
            'extractValue' => function ($value) {
                if (preg_match('/align(full|wide|left|right|center)/', $value, $matches)) {
                    return $matches[1];
                }
                return null;
            }
        ];
    }

    // Section behaviour / reveal / parallax extensions on core/group.
    // The schema is registered here so values serialise into the saved markup
    // and are exposed via GraphQL regardless of post type. The editor UI that
    // sets these lives in resources/scripts/editor.js.
    if ($name === 'core/group') {
        $args['attributes']['sectionBehavior'] = ['type' => 'string', 'default' => 'normal'];
        $args['attributes']['sectionMinHeight'] = ['type' => 'string', 'default' => 'auto'];
        $args['attributes']['sectionContentAlign'] = ['type' => 'string', 'default' => 'center'];
        $args['attributes']['sectionReveal'] = ['type' => 'string', 'default' => 'none'];
        $args['attributes']['sectionRevealDirection'] = ['type' => 'string', 'default' => 'up'];
        $args['attributes']['sectionRevealStagger'] = ['type' => 'number', 'default' => 60];
        $args['attributes']['sectionParallax'] = ['type' => 'boolean', 'default' => false];
    }

    return $args;
}, 20, 2);

/**
 * Fix core block attribute nullability for GraphQL.
 *
 * wp-graphql-content-blocks wraps attributes with a `default` value as non_null (String!),
 * but core blocks declare the same fields as nullable (String). This causes a GraphQL
 * validation conflict when both are queried in the same fragment on EditorBlock.
 */
function unwrap_nonnull_fields(array $fields, array $nullable_fields): array
{
    foreach ($nullable_fields as $key) {
        if (isset($fields[$key]['type']) && is_array($fields[$key]['type']) && isset($fields[$key]['type']['non_null'])) {
            $fields[$key]['type'] = $fields[$key]['type']['non_null'];
        }
    }
    return $fields;
}

// Nullable attribute names shared across block nullability filters
$nullable_attrs = ['backgroundColor', 'textColor', 'align', 'style', 'className'];

// Fix core/spacer height nullability (String! conflicts with CoreImage height which is String)
add_filter('graphql_coreSpacerAttributes_fields', function ($fields) {
    return unwrap_nonnull_fields($fields, ['height']);
});

// Fix core/columns and core/column attribute nullability
add_filter('graphql_coreColumnsAttributes_fields', function ($fields) use ($nullable_attrs) {
    return unwrap_nonnull_fields($fields, $nullable_attrs);
});

add_filter('graphql_coreColumnAttributes_fields', function ($fields) use ($nullable_attrs) {
    return unwrap_nonnull_fields($fields, $nullable_attrs);
});

// Fix core/group attribute nullability, including the section extensions
add_filter('graphql_coreGroupAttributes_fields', function ($fields) use ($nullable_attrs) {
    return unwrap_nonnull_fields(
        $fields,
        array_merge($nullable_attrs, [
            'sectionBehavior',
            'sectionMinHeight',
            'sectionContentAlign',
            'sectionReveal',
            'sectionRevealDirection',
            'sectionRevealStagger',
            'sectionParallax',
        ])
    );
});

/**
 * Resolved post types and fields for blocks that display post lists.
 *
 * Registers ResolvedPost (with featured image) and adds a resolvedPosts
 * field to CoreLatestPosts and CoreQuery so the frontend gets full post
 * data without a separate query.
 */
add_action('graphql_register_types', function () {
    register_graphql_object_type('ResolvedPostImageSize', [
        'description' => 'An image size variant.',
        'fields'      => [
            'sourceUrl' => ['type' => 'String'],
            'width'     => ['type' => 'String'],
            'height'    => ['type' => 'String'],
            'name'      => ['type' => 'String'],
            'mimeType'  => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('ResolvedPostImage', [
        'description' => 'A featured image for a resolved post.',
        'fields'      => [
            'sourceUrl' => ['type' => 'String'],
            'altText'   => ['type' => 'String'],
            'sizes'     => ['type' => ['list_of' => 'ResolvedPostImageSize']],
        ],
    ]);

    register_graphql_object_type('ResolvedPost', [
        'description' => 'A resolved post with title, date, URI, and featured image.',
        'fields'      => [
            'title'         => ['type' => 'String'],
            'date'          => ['type' => 'String'],
            'uri'           => ['type' => 'String'],
            'excerpt'       => ['type' => 'String'],
            'featuredImage' => ['type' => 'ResolvedPostImage'],
        ],
    ]);

    // Shared resolver: builds WP_Query args from block attributes
    $resolve_posts = function (array $attrs): array {
        $args = [
            'post_type'      => $attrs['postType'] ?? 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $attrs['postsToShow'] ?? $attrs['perPage'] ?? 5,
            'order'          => strtoupper($attrs['order'] ?? 'desc'),
            'orderby'        => $attrs['orderBy'] ?? 'date',
        ];

        if (! empty($attrs['offset'])) {
            $args['offset'] = (int) $attrs['offset'];
        }

        // core/latest-posts uses categories: [{id: 1}, ...]
        if (! empty($attrs['categories'])) {
            $cat_ids = array_map(function ($cat) {
                return is_array($cat) ? ($cat['id'] ?? 0) : (int) $cat;
            }, $attrs['categories']);
            $cat_ids = array_filter($cat_ids);
            if ($cat_ids) {
                $args['category__in'] = $cat_ids;
            }
        }

        // core/query uses taxQuery: { category: [id, ...], post_tag: [id, ...] }
        if (! empty($attrs['taxQuery'])) {
            $tax_query = [];
            foreach ($attrs['taxQuery'] as $taxonomy => $term_ids) {
                if (! empty($term_ids)) {
                    $tax_query[] = [
                        'taxonomy' => $taxonomy,
                        'field'    => 'term_id',
                        'terms'    => array_map('intval', (array) $term_ids),
                    ];
                }
            }
            if ($tax_query) {
                $args['tax_query'] = $tax_query;
            }
        }

        $query = new \WP_Query($args);
        $posts = [];

        foreach ($query->posts as $post) {
            $post_data = [
                'title'   => $post->post_title,
                'date'    => $post->post_date,
                'uri'     => wp_make_link_relative(get_permalink($post)),
                'excerpt' => get_the_excerpt($post),
            ];

            $thumbnail_id = get_post_thumbnail_id($post->ID);
            if ($thumbnail_id) {
                $metadata  = wp_get_attachment_metadata($thumbnail_id);
                $sizes     = [];

                if (! empty($metadata['sizes'])) {
                    $upload_dir = wp_get_upload_dir();
                    $file_dir   = dirname($metadata['file']);

                    foreach ($metadata['sizes'] as $name => $size) {
                        $sizes[] = [
                            'sourceUrl' => $upload_dir['baseurl'] . '/' . $file_dir . '/' . $size['file'],
                            'width'     => (string) $size['width'],
                            'height'    => (string) $size['height'],
                            'name'      => strtoupper(str_replace('-', '_', $name)),
                            'mimeType'  => $size['mime-type'],
                        ];
                    }
                }

                $post_data['featuredImage'] = [
                    'sourceUrl' => wp_get_attachment_url($thumbnail_id),
                    'altText'   => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) ?: '',
                    'sizes'     => $sizes,
                ];
            }

            $posts[] = $post_data;
        }

        return $posts;
    };

    // core/latest-posts
    register_graphql_field('CoreLatestPosts', 'resolvedPosts', [
        'type'        => ['list_of' => 'ResolvedPost'],
        'description' => 'The resolved posts based on block attributes.',
        'resolve'     => function ($block) use ($resolve_posts) {
            return $resolve_posts($block['attrs'] ?? []);
        },
    ]);

    // core/query (Query Loop)
    register_graphql_field('CoreQuery', 'resolvedPosts', [
        'type'        => ['list_of' => 'ResolvedPost'],
        'description' => 'The resolved posts based on query block attributes.',
        'resolve'     => function ($block) use ($resolve_posts) {
            // core/query nests its params under attrs.query
            $attrs = $block['attrs'] ?? [];
            $query = $attrs['query'] ?? [];
            return $resolve_posts($query);
        },
    ]);

    // Top-level query for paginated posts (used by frontend pagination)
    register_graphql_field('RootQuery', 'queryPosts', [
        'type'        => ['list_of' => 'ResolvedPost'],
        'description' => 'Fetch posts with pagination for any post type.',
        'args'        => [
            'postType' => ['type' => 'String'],
            'perPage'  => ['type' => 'Int'],
            'offset'   => ['type' => 'Int'],
            'order'    => ['type' => 'String'],
            'orderBy'  => ['type' => 'String'],
        ],
        'resolve'     => function ($root, $args) use ($resolve_posts) {
            return $resolve_posts($args);
        },
    ]);

    // Total post count for pagination
    register_graphql_field('RootQuery', 'queryPostsCount', [
        'type'        => 'Int',
        'description' => 'Get total post count for pagination.',
        'args'        => [
            'postType' => ['type' => 'String'],
        ],
        'resolve'     => function ($root, $args) {
            $post_type = $args['postType'] ?? 'post';
            $counts = wp_count_posts($post_type);
            return (int) ($counts->publish ?? 0);
        },
    ]);
});
