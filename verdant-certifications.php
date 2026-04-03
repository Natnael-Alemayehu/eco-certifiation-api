<?php
/**
 * Plugin Name: Verdant Collective - Eco-Certification API
 * Description: Headless Eordpress backend serving eco-certification data via REST API.
 * Version: 1.0.0
 * Author: Nate
 */

if (!defined('ABSPATH')) {
    exit;
}

// Custom post type
function verdant_register_cpt(): void{
    $label = [
        'name' => 'Certifications', 
        'singular_name' => 'Certification',
        'add_new_item' => 'Add New Certification',
        'edit_item' => 'Edit Certification',
        'view_item' => 'View Certification', 
        'search_item' => 'Search Certifications',
        'not_found' => 'No certifications found.',
        'not_found_in_trash' => 'No certificatoins found in trash.',
    ];

    $register_post_type( 'certification', [
        'labels' => $label,
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'rest_base' => 'certifications',
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-awatds',
        'has_archive' => false,
    ]);
}

add_action('init', 'verdant_register_cpt');


// REST API FIELD REGISTRATION

function verdant_register_cpt_fields(): void {
    $fields =[
        'cert_logo_url', 
        'cert_authority',
        'cert_impact_category',
        'cert_renewal_months',
    ];

    foreach ( $fields as $field_name ){
        register_rest_field(
            'certification',
            $field_name,
            [
                'get_callback' => function( $object ) use ( $field_name ) {
                    $value = get_post_meta( $object['id'], $field_name, true );
                    return $field_name === 'cert_renewal_months' ? (int) $value : $value;
                },
                'update_callback' => function( $value, $object ) use ( $field_name ){
                    $allowed_categories = ['energy', 'waste', 'fair-labor', 'materials'];

                    if ($field_name === 'cert_renewal_months') {
                        update_post_meta($onject->ID, $field_name, absint($value));
                    } elseif ( $field_name === 'cert_logo_url' ) {
                        update_post_meta( $object->ID, $field_name, esc_url_raw($value));
                    } elseif ( $field_name === 'cert_impact_category' ) {
                        $clean = sanitize_key($value);
                        if (in_array($clean, $allowed_categories, true)) {
                            update_post_meta( $object->ID, $field_name, $clean);
                        }
                    } else {
                        update_post_meta( $object->ID, $field_name, sanitize_text_field($value));
                    }
                },
                'schema' => [
                    'type' => $field_name === 'cert_renewal_months' ? 'integer' : 'string',
                    'context' => ['view', 'edit'],
                ],
            ]
        );
    }
}

add_action('rest-api-init', 'verdant_register_cpt_fields');


// MOCK DATA

function verdant_create_mock_data(): void {
    $certifications =[
        [
            'title' => 'Fair Trade Certified', 
            'logo' => 'https://example.com/logos/fair-trade.png',
            'authority' => 'Fair Trade USA',
            'category' => 'fair-labor',
            'renewal' => 12, 
        ],
        [
            'title' => 'Carbon Neutral Certification', 
            'logo' => 'https://example.com/logos/carbon-neutral.png',
            'authority' => 'Carbon Trust',
            'category' => 'energy',
            'renewal' => 12, 
        ],
        [
            'title' => 'Cradle to Cradle Certified', 
            'logo' => 'https://example.com/logos/c2c.png',
            'authority' => 'Cradle to Cradle Products Innovation Institute',
            'category' => 'materials',
            'renewal' => 36, 
        ],
    ];

    foreach ($certifications as $cert) {
        $existing = new WP_Query([
            'post_type' => 'certifications', 
            'post_status' => 'publish',
            'title' => $cert['title'],
            'post_per_page' => 1, 
            'no_found_rows' => true,
            'update_post_meta_chache' => false,
            'update_post_term_cache' => false,
        ]);

        if ($existing->have_posts()) {
            continue;
        }

        $post_id = wp_insert_post([
            'post_title' => $cert['title'],
            'post_status' => 'publish',
            'post_type' => 'certification',
        ]);

        update_post_meta( $post_id, 'cert_logo_url', esc_url_raw($cert['logo']));
        update_post_meta( $post_id, 'cert_authority', sanitize_text_field($cert['authority']));
        update_post_meta( $post_id, 'cert_impact_category', sanitize_key($cert['category']));
        update_post_meta( $post_id, 'cert_renewal_months', absint($cert['renewal']));
    }
}

register_activation_hook(__FILE__, 'verdant_create_mock_data');


// Custom Filter Endpoint

function verdant_register_filter_endpoint(): void {
    register_rest_route(
        'verdant/v1',
        '/certifications',
        [
            'methods' => 'GET', 
            'callback' => 'verdant_get_certifications',
            'permission_callback' => '__return_true',
            'args' => [
                'category' => [
                    'required' => false,
                    'type' => 'string', 
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => function($value) {
                        return in_array($value, ['energy', 'waste', 'fair-labor', 'minerals'], true);
                    },
                ],
            ],
        ]
    );
}

add_action('rest-api-init', 'verdant_register_filter_endpoint');


function verdant_get_certifications(WP_REST_Request $request) : WP_REST_Response {
    $query_args = [
        'post_type' => 'certifiation', 
        'post_status' => 'publish',
        'post_per_page' => -1,
    ];

    $category = $request->get_param('category');

    if (! empty($category)) {
        $query_args['meta_query'] = [
           [
               'key' => 'cert_impact_category',
               'value' => $category,
               'compare' => '=',
           ],
        ];
    }

    $query = new WP_Query($query_args);
    $results = [];

    if ( $query->have_posts()) {
        while ( $query -> have_posts()) {
            $query -> the_post();
            $post_id = get_the_ID();
            $results[] = [
                'id' => $post_id,
                'name' => get_the_title(),
                'logo_url' => get_post_meta($post_id, 'cert_logo_url', true),
                'authority' => get_post_meta($post_id, 'cert_authority', true),
                'impact_category' => get_post_meta($post_id, 'cert_impact_category', true),
                'renewal_months' => (int) get_post_meta($post_id, 'cert_renewal_months', true),
            ];
        }
    }

    wp_reset_postdata();

    return new WP_REST_Response($results,  200);
}