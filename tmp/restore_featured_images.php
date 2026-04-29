<?php
$dry_run  = false;
$log_file = '/tmp/restore_images_log.csv';

global $wpdb;

$products = get_posts([
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'post_status'    => ['publish', 'draft', 'private'],
    'meta_query'     => [[
        'key'     => '_thumbnail_id',
        'compare' => 'NOT EXISTS',
    ]],
]);

$total    = count($products);
$restored = 0;
$skipped  = 0;
$log      = [['produkt_id', 'produkt_slug', 'produkt_titel', 'attachment_id', 'attachment_datei', 'status', 'match_methode']];

echo ($dry_run ? "[DRY-RUN] " : "[SCHARF] ") . "{$total} Produkte ohne Featured Image gefunden.\n\n";

foreach ($products as $product) {
    $slug  = $product->post_name;
    $title = $product->post_title;
    $match = '';

    // Methode 1: Slug in guid oder post_name
    $attachment = $wpdb->get_row($wpdb->prepare(
        "SELECT ID, guid, post_name FROM {$wpdb->posts}
         WHERE post_type = 'attachment'
           AND post_mime_type LIKE 'image/%%'
           AND (guid LIKE %s OR post_name LIKE %s)
         ORDER BY ID ASC LIMIT 1",
        '%' . $wpdb->esc_like($slug) . '%',
        '%' . $wpdb->esc_like($slug) . '%'
    ));
    if ($attachment) $match = 'slug';

    // Methode 2: Titel (sanitized) in guid oder post_name
    if (! $attachment) {
        $title_slug = sanitize_title($title);
        $attachment = $wpdb->get_row($wpdb->prepare(
            "SELECT ID, guid, post_name FROM {$wpdb->posts}
             WHERE post_type = 'attachment'
               AND post_mime_type LIKE 'image/%%'
               AND (guid LIKE %s OR post_name LIKE %s OR post_title LIKE %s)
             ORDER BY ID ASC LIMIT 1",
            '%' . $wpdb->esc_like($title_slug) . '%',
            '%' . $wpdb->esc_like($title_slug) . '%',
            '%' . $wpdb->esc_like($title)       . '%'
        ));
        if ($attachment) $match = 'title';
    }

    // Methode 3: SKU-Basis (erster Teil vor Bindestrich, mind. 4 Zeichen)
    if (! $attachment) {
        $sku = get_post_meta($product->ID, '_sku', true);
        if ($sku) {
            $sku_parts = explode('-', $sku);
            $sku_base  = $sku_parts[0];
            if (strlen($sku_base) >= 4) {
                $attachment = $wpdb->get_row($wpdb->prepare(
                    "SELECT ID, guid, post_name FROM {$wpdb->posts}
                     WHERE post_type = 'attachment'
                       AND post_mime_type LIKE 'image/%%'
                       AND (guid LIKE %s OR post_name LIKE %s)
                     ORDER BY ID ASC LIMIT 1",
                    '%' . $wpdb->esc_like($sku_base) . '%',
                    '%' . $wpdb->esc_like($sku_base) . '%'
                ));
                if ($attachment) $match = 'sku-base:' . $sku_base;
            }

            // Methode 4: Volle SKU (sanitized)
            if (! $attachment) {
                $sku_clean = sanitize_title($sku);
                $attachment = $wpdb->get_row($wpdb->prepare(
                    "SELECT ID, guid, post_name FROM {$wpdb->posts}
                     WHERE post_type = 'attachment'
                       AND post_mime_type LIKE 'image/%%'
                       AND (guid LIKE %s OR post_name LIKE %s)
                     ORDER BY ID ASC LIMIT 1",
                    '%' . $wpdb->esc_like($sku_clean) . '%',
                    '%' . $wpdb->esc_like($sku_clean) . '%'
                ));
                if ($attachment) $match = 'sku-full:' . $sku_clean;
            }
        }
    }

    if ($attachment) {
        $filename = basename($attachment->guid);
        if (! $dry_run) {
            set_post_thumbnail($product->ID, $attachment->ID);
        }
        echo "✓ [{$product->ID}] {$slug}  →  {$filename} [{$match}]\n";
        $log[] = [$product->ID, $slug, $title, $attachment->ID, $filename, 'restored', $match];
        $restored++;
    } else {
        echo "✗ [{$product->ID}] {$slug}  →  kein Match\n";
        $log[] = [$product->ID, $slug, $title, '', '', 'no_match', ''];
        $skipped++;
    }
}

$fh = fopen($log_file, 'w');
foreach ($log as $row) fputcsv($fh, $row);
fclose($fh);

echo "\n" . ($dry_run ? "[DRY-RUN] Keine Änderungen gespeichert.\n" : "[SCHARF] Änderungen gespeichert.\n");
echo "Ergebnis: {$restored} wiederhergestellt, {$skipped} ohne Match (von {$total})\n";
echo "Log: {$log_file}\n";
