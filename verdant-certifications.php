<?php
/**
 * Plugin Name: Verdant Collective - Eco-Certification API
 * Description: Headless WordPress backend serving eco-certification data via REST API.
 * Version: 1.0.0
 * Author: Nate
 */

if (!defined('ABSPATH')) {
    exit;
}

// Custom post type
function verdant_register_cpt(): void {
    $labels = [
        'name' => 'Certifications', 
        'singular_name' => 'Certification',
        'add_new_item' => 'Add New Certification',
        'edit_item' => 'Edit Certification',
        'view_item' => 'View Certification', 
        'search_item' => 'Search Certifications',
        'not_found' => 'No certifications found.',
        'not_found_in_trash' => 'No certificatoins found in trash.',
    ];

    register_post_type('certification', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'rest_base' => 'certifications',
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-awards',
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
                        update_post_meta($object->ID, $field_name, absint($value));
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
            'post_type' => 'certification',
            'post_status' => 'publish',
            'title' => $cert['title'],
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
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
                        return in_array($value, ['energy', 'waste', 'fair-labor', 'materials'], true);
                    },
                ],
            ],
        ]
    );
}

add_action('rest-api-init', 'verdant_register_filter_endpoint');


function verdant_get_certifications(WP_REST_Request $request) : WP_REST_Response {
    $query_args = [
        'post_type' => 'certification',
        'post_status' => 'publish',
        'posts_per_page' => -1,
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

// Headless Hardening

function verdant_block_frontend(): void {
    if ( is_admin() ) return;
    if ( defined('REST_REQUEST') && REST_REQUEST ) return;
    if ( defined('DOING_CRON') && DOING_CRON ) return;
    if ( defined('DOING_AJAX') && DOING_AJAX ) return;
    if ( strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-json/') !== false ) return;

    wp_die(
        'This WordPress installation is a headless API backend. No frontend is available.',
        'API Backend Only',
        ['response' => 403]
    );
}

add_action('template_redirect' , 'verdant_block_frontend');


function verdant_disable_gutenberg(bool $use_block_editor, string $post_type): bool {
    return $post_type === 'certification' ? false : $use_block_editor;
}

add_filter('use_block_editor_for_post_type', 'verdant_disable_gutenberg', 10, 2);

function verdant_clean_head(): void {
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'feed_links_extra', 3);
}

add_action( 'init', 'verdant_clean_head');


// ADMIN BOX

function verdant_add_meta_boxes(): void {
    add_meta_box(
        'verdant_cert_fields',
        'Certificaion Details',
        'verdant_render_meta_box',
        'certification',
        'normal',
        'high'
    );
}

add_action( 'add_meta_boxes', 'verdant_add_meta_boxes' );

function verdant_render_meta_box( WP_Post $post): void {
    wp_nonce_field('verdant_save_meta', 'verdant_meta_nonce');

    $logo = get_post_meta($post->ID, 'cert_logo_url', true);
    $authority = get_post_meta($post->ID, 'cert_authority', true);
    $category = get_post_meta($post->ID, 'cert_impact_category', true);
    $renewal = get_post_meta($post->ID, 'cert_renewal_months', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="cert_logo_url"> Organization Logo URL </label></th>
            <td>
                <input type="url" id="cert_logo_url" name="cert_logo_url"
                    value="<?php echo esc_url($logo); ?>"
                    style="width:100%;" placeholder="https://example.com/logo.png">
            </td>
        </tr>
        <tr>
            <th><label for="cert_authority"> Certification Authority </label></th>
            <td>
                <input type="text" id="cert_authority" name="cert_authority"
                    value="<?php echo esc_attr($authority); ?>"
                    style="width:100%;" placeholder="e.g Fair Trade USA">
            </td>
        </tr>
        <tr>
            <th><label for="cert_impact_category">Impact Category</label></th>
            <td>
                <select id="cert_impact_category" name="cert_impact_category">
                    <option value="">- Select a category -</option>
                    <?php
                    $options = [
                        'energy' => 'Energy',
                        'waste' => 'Waste',
                        'fair-labor' => 'Fair Labor',
                        'materials' => 'Materials',
                    ];

                    foreach ($options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>"
                            <?php selected($category, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="cert_renewal_months"> Renewal Frequency (months) </label></th>
            <td>
                <input type="number" id="cert_renewal_months" name="cert_renewal_months"
                    value="<?php echo esc_attr($renewal); ?>"
                    min="1" placeholder="e.g 12">
            </td>
        </tr>
    </table>
    <?php
}

function verdant_save_meta( int $post_id ): void {
    if (! isset($_POST['verdant_meta_nonce']) ||
        ! wp_verify_nonce($_POST['verdant_meta_nonce'], 'verdant_save_meta')) {
        return;
    }

    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! current_user_can('edit_post', $post_id)) return;

    if ( isset($_POST['cert_logo_url'])) {
        update_post_meta($post_id, 'cert_logo_url', esc_url_raw($_POST['cert_logo_url']));
    }

    if ( isset($_POST['cert_impact_category'])) {
        $allowed = ['energy', 'waste', 'fair-labor', 'materials'];
        $category = sanitize_key($_POST['cert_impact_category']);
        if ( in_array($category, $allowed, true)) {
            update_post_meta($post_id, 'cert_impact_category', $category);
        }
    }

    if (isset($_POST['cert_renewal_months'])) {
        update_post_meta($post_id, 'cert_renewal_months', absint($_POST['cert_renewal_months']));
    }
}

add_action('save_post_certification', 'verdant_save_meta');