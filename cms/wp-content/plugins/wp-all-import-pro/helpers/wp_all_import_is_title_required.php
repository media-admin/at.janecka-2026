<?php

function wp_all_import_is_title_required( $custom_type ) {
    // Import types that don't require title/content fields
    // - shop_order: WooCommerce orders use order number, not title
    // - import_users/shop_customer: Users have usernames/emails, not titles
    // - comments/woo_reviews: Comments have content but no title field
    // - taxonomies: Taxonomy terms have names, not titles
    // - gf_entries: Gravity Forms entries have form fields, not title/content
    $types_title_not_required = array(
        'shop_order',
        'import_users',
        'shop_customer',
        'comments',
        'woo_reviews',
        'gf_entries'
    );
    $supports_title = !in_array($custom_type, $types_title_not_required);

    return apply_filters('pmxi_types_current_type_supports_title', $supports_title, $custom_type);
}