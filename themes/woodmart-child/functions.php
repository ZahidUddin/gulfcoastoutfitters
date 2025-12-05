<?php
/**
 * Enqueue script and styles for child theme
 */
function woodmart_child_enqueue_styles() {
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'woodmart-style' ), woodmart_get_theme_info( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'woodmart_child_enqueue_styles', 10010 );


/**
 * Find which image attachments are USED vs UNUSED across the site.
 * - Scans: post content (classic + Gutenberg blocks), featured images,
 *   WooCommerce galleries, and ANY postmeta that stores attachment IDs (incl. ACF).
 * - Returns: ['used_ids' => int[], 'unused_ids' => int[], 'summary' => array]
 *
 * Note: Won’t catch images referenced only in custom CSS/JS or remote CDNs.
 */
function tcdb_find_used_images( $batch_size = 300 ) {
    global $wpdb;

    @set_time_limit(0);
    $uploads_base = wp_get_upload_dir()['baseurl'];

    // 1) All image attachment IDs
    $all_attachment_ids = $wpdb->get_col("
        SELECT ID
        FROM {$wpdb->posts}
        WHERE post_type = 'attachment'
          AND post_mime_type LIKE 'image/%'
    ");

    $all_set = array_fill_keys(array_map('intval', $all_attachment_ids), true);
    $used    = []; // map of attachment_id => true
    $url_to_id_cache = [];

    $mark_used = static function($id) use (&$used, $all_set) {
        $id = (int) $id;
        if ($id > 0 && isset($all_set[$id])) $used[$id] = true;
    };

    // 2) Featured images
    $thumb_ids = $wpdb->get_col("
        SELECT DISTINCT meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_thumbnail_id' AND meta_value <> ''
    ");
    foreach ($thumb_ids as $id) $mark_used($id);

    // 3) Woo gallery (comma-separated IDs)
    $galleries = $wpdb->get_col("
        SELECT meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_product_image_gallery' AND meta_value <> ''
    ");
    foreach ($galleries as $csv) {
        foreach (array_filter(array_map('intval', explode(',', (string)$csv))) as $id) {
            $mark_used($id);
        }
    }

    // 4) Any postmeta that directly stores an attachment ID (catches ACF image fields etc.)
    $meta_ids = $wpdb->get_col("
        SELECT DISTINCT CAST(pm.meta_value AS UNSIGNED)
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} att
            ON att.ID = CAST(pm.meta_value AS UNSIGNED)
        WHERE pm.meta_value REGEXP '^[0-9]+$'
          AND att.post_type = 'attachment'
          AND att.post_mime_type LIKE 'image/%'
    ");
    foreach ($meta_ids as $id) $mark_used($id);

    // 5) Scan post content (all non-attachment post types) in batches
    $scan_post_ids = $wpdb->get_col("
        SELECT ID
        FROM {$wpdb->posts}
        WHERE post_type NOT IN ('attachment','revision','nav_menu_item')
          AND post_status IN ('publish','draft','future','private')
    ");

    $extract_from_content = static function($content) use ($uploads_base, &$url_to_id_cache, $mark_used) {
        // 5a) If Gutenberg, parse blocks to get IDs
        if (function_exists('parse_blocks')) {
            $stack = parse_blocks($content);
            while (!empty($stack)) {
                $block = array_pop($stack);
                if (!is_array($block)) continue;

                // core/image
                if (!empty($block['blockName']) && $block['blockName'] === 'core/image') {
                    if (!empty($block['attrs']['id'])) $mark_used($block['attrs']['id']);
                }
                // core/gallery
                if (!empty($block['blockName']) && $block['blockName'] === 'core/gallery') {
                    if (!empty($block['attrs']['ids']) && is_array($block['attrs']['ids'])) {
                        foreach ($block['attrs']['ids'] as $id) $mark_used($id);
                    }
                }
                // Recurse
                if (!empty($block['innerBlocks'])) {
                    foreach ($block['innerBlocks'] as $ib) $stack[] = $ib;
                }
            }
        }

        // 5b) Classic content: classes like wp-image-123
        if (preg_match_all('/wp-image-([0-9]+)/i', $content, $m)) {
            foreach ($m[1] as $id) $mark_used($id);
        }

        // 5c) <img src="..."> URLs → attachment IDs
        if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $m2)) {
            foreach ($m2[1] as $url) {
                // Only check local uploads to avoid external/CDN images
                if ($uploads_base && strpos($url, $uploads_base) === false) continue;

                if (isset($url_to_id_cache[$url])) {
                    $id = $url_to_id_cache[$url];
                } else {
                    $id = attachment_url_to_postid($url);
                    $url_to_id_cache[$url] = $id;
                }
                if ($id) $mark_used($id);
            }
        }
    };

    for ($i = 0; $i < count($scan_post_ids); $i += $batch_size) {
        $chunk = array_slice($scan_post_ids, $i, $batch_size);
        // Pull content in one go
        $placeholders = implode(',', array_fill(0, count($chunk), '%d'));
        $rows = $wpdb->get_results(
            $wpdb->prepare("
                SELECT ID, post_content
                FROM {$wpdb->posts}
                WHERE ID IN ($placeholders)
            ", $chunk),
            ARRAY_A
        );
        foreach ($rows as $row) {
            $content = (string) $row['post_content'];
            if ($content !== '') $extract_from_content($content);
        }
    }

    // Finalize
    $used_ids   = array_map('intval', array_keys($used));
    sort($used_ids);
    $unused_ids = array_values(array_diff($all_attachment_ids, $used_ids));
    sort($unused_ids);

    return [
        'used_ids'   => $used_ids,
        'unused_ids' => $unused_ids,
        'summary'    => [
            'total_images' => count($all_attachment_ids),
            'used'         => count($used_ids),
            'unused'       => count($unused_ids),
        ],
    ];
}

function load_media_library() {
    wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'load_media_library');


// 1) Register slider CSS & JS
add_action( 'wp_enqueue_scripts', 'my_home_slider_register_assets' );
function my_home_slider_register_assets() {
    // Change paths if your files are elsewhere
    wp_register_style(
        'home-slider-css',
        get_stylesheet_directory_uri() . '/home-slider.css',
        array(),
        '1.0.0'
    );

    wp_register_script(
        'home-slider-js',
        get_stylesheet_directory_uri() . '/home-slider.js',
        array( 'jquery' ), // remove 'jquery' if not needed
        '1.0.0',
        true
    );
}

// 2) Shortcode: [home_slider]
function my_home_slider_shortcode( $atts = array(), $content = null ) {
    // Enqueue only when shortcode is used
    wp_enqueue_style( 'home-slider-css' );
    wp_enqueue_script( 'home-slider-js' );

    ob_start();

    // If home-slider.php is in the theme root
    $template = locate_template( 'home-slider.php' );
    if ( $template ) {
        include $template;
    } else {
        echo '<!-- home-slider.php not found -->';
    }

    return ob_get_clean();
}
add_shortcode( 'home_slider', 'my_home_slider_shortcode' );


add_action( 'wp_enqueue_scripts', 'disable_cart_fragments', 100 );
function disable_cart_fragments() {
    if ( ! is_cart() && ! is_checkout() && ! is_product() ) {
        wp_dequeue_script( 'wc-cart-fragments' );
    }
}

add_action('plugins_loaded', function(){
    remove_filter( 'load_textdomain_mofile', 'wp_load_textdomain_just_in_time' );
}, 999);

remove_action('edited_product_cat', 'wc_term_recount');
remove_action('delete_product_cat', 'wc_term_recount');
remove_action('create_product_cat', 'wc_term_recount');
