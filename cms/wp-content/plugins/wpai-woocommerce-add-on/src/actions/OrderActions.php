<?php

namespace wpai_woocommerce_add_on\actions;

use wpai_woocommerce_add_on\parser\Parser;
use wpai_woocommerce_add_on\XmlImportWooCommerceService;

/**
 * Created by PhpStorm.
 * User: cmd
 * Date: 11/16/17
 * Time: 3:44 PM
 */

class OrderActions extends Actions {

    /**
     * OrderActions constructor.
     * @param Parser $parser
     */
    public function __construct(Parser $parser) {

        parent::__construct($parser);

        if (!has_filter('wp_all_import_is_post_to_skip')) {
            add_filter('wp_all_import_is_post_to_skip', array(
                &$this,
                'wp_all_import_is_post_to_skip'
            ), 10, 5);
        }

        if (!has_filter('wp_all_import_combine_article_data')) {
            add_filter('wp_all_import_combine_article_data', array(
                &$this,
                'wp_all_import_combine_article_data'
            ), 10, 4);
        }
    }

    /**
     *
     * When users are matching to existing customers and/or products and no match it found,
     * WP All Import doesn't have enough information to import that order, so the whole order will be skipped.
     *
     * @param $is_post_to_skip
     * @param $import_id
     * @param $current_xml_node
     * @param $index
     * @param $post_to_update_id
     * @return bool
     */
    public function wp_all_import_is_post_to_skip($is_post_to_skip, $import_id, $current_xml_node, $index, $post_to_update_id) {

        $order_title = 'Order &ndash; ' . date_i18n('F j, Y @ h:i A', strtotime($this->getParser()->getData()['pmwi_order']['date'][$index]));

        if (empty($post_to_update_id) or $this->getParser()->getImport()->options['update_all_data'] == 'yes' or $this->getParser()->getImport()->options['is_update_billing_details']) {
            if ($this->getParser()->getImport()->options['pmwi_order']['billing_source'] == 'existing') {
                $customer = $this->getParser()->get_existing_customer('billing_source', $index);
                if (empty($customer) && empty($this->getParser()->getImport()->options['pmwi_order']['is_guest_matching'])) {
                    $this->getParser()->getLogger() and call_user_func($this->getParser()->getLogger(), sprintf(__('<b>SKIPPED</b>: %s Existing customer not found for Order `%s`.', 'wpai_woocommerce_addon_plugin'), $this->getParser()->get_existing_customer_for_logger('billing_source', $index), $order_title));
                    $is_post_to_skip = TRUE;
                }
            }
        }

        if ($is_post_to_skip) {
            return $is_post_to_skip;
        }

        return $is_post_to_skip;
    }

    /**
     * @param $articleData
     * @param $post_type
     * @param $import_id
     * @param $index
     * @return mixed
     */
    public function wp_all_import_combine_article_data($articleData, $post_type, $import_id, $index) {
        if ($post_type == 'shop_order' && empty($articleData['post_title'])) {
            $articleData['post_title'] = 'Order &ndash; ' . date_i18n('F j, Y @ h:i A', strtotime($this->getParser()->getData()['pmwi_order']['date'][$index]));
        }
        return $articleData;
    }
}
