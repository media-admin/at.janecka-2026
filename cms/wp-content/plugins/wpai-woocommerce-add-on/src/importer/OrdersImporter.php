<?php

namespace wpai_woocommerce_add_on\importer;

use WC_Data_Store;
use wpai_woocommerce_add_on\importer\orders\ImportOrderAddress;
use wpai_woocommerce_add_on\importer\orders\ImportOrderBase;
use wpai_woocommerce_add_on\importer\orders\ImportOrderDetails;
use wpai_woocommerce_add_on\importer\orders\ImportOrderNotes;
use wpai_woocommerce_add_on\importer\orders\ImportOrderPayment;
use wpai_woocommerce_add_on\importer\orders\ImportOrderRefunds;
use wpai_woocommerce_add_on\importer\orders\ImportOrderTotal;
use wpai_woocommerce_add_on\importer\orders\items\ImportOrderCouponItems;
use wpai_woocommerce_add_on\importer\orders\items\ImportOrderFeeItems;
use wpai_woocommerce_add_on\importer\orders\items\ImportOrderProductItems;
use wpai_woocommerce_add_on\importer\orders\items\ImportOrderShippingItems;

/**
 * Class OrdersImporter
 * @package wpai_woocommerce_add_on\importer
 */
class OrdersImporter extends Importer {

    /**
     * @var array
     */
    public $importers = array();

    /**
     *
     * Import WooCommerce Order
     *
     * @return array
     */
    public function import() {

        $data = $this->getParsedData()['pmwi_order'];

        // Block email notifications during import process.
        if (!empty($this->getImport()->options['do_not_send_order_notifications'])) {
            add_filter('woocommerce_email_classes', [$this, 'woocommerce_email_classes'], 99, 1);
        }

		// Block automatic order notes unless they're explicitly allowed.
	    if( empty($this->getImport()->options['pmwi_order']['notes_add_auto_order_status_notes'])) {
		    add_filter( 'woocommerce_new_order_note_data', [ $this, 'woocommerce_new_order_note_data' ], 10, 2 );
	    }

        $order = wc_get_order($this->index->getPid());

        $this->importers['orderAddress'] = new ImportOrderAddress($this->getIndexObject(), $this->getOptions(), $order, $data);
        $this->importers['orderPayment'] = new ImportOrderPayment($this->getIndexObject(), $this->getOptions(), $order, $data);
        $this->importers['orderProductItems'] = new ImportOrderProductItems($this->getIndexObject(), $this->getOptions(), $order, $data);
        $this->importers['orderFeeItems'] = new ImportOrderFeeItems($this->getIndexObject(), $this->getOptions(), $order, $data);
        $this->importers['orderCouponItems'] = new ImportOrderCouponItems($this->getIndexObject(), $this->getOptions(), $order, $data);
        $this->importers['orderShippingItems'] = new ImportOrderShippingItems($this->getIndexObject(), $this->getOptions(), $order, $data);
        $this->importers['orderTotal'] = new ImportOrderTotal($this->getIndexObject(), $this->getOptions(), $order, $data);
        $this->importers['orderRefunds'] = new ImportOrderRefunds($this->getIndexObject(), $this->getOptions(), $order, $data);
        $this->importers['orderNotes'] = new ImportOrderNotes($this->getIndexObject(), $this->getOptions(), $order, $data);
        $this->importers['orderDetails'] = new ImportOrderDetails($this->getIndexObject(), $this->getOptions(), $order, $data);

        /** @var ImportOrderBase $importer */
        foreach ($this->importers as $importer) {
            $importer->import();
        }

        $order->update_taxes();

		// Remove order note filter.
	    remove_filter('woocommerce_new_order_note_data', [$this, 'woocommerce_new_order_note_data']);

    }

    /**
     * @param $emails
     * @return array
     */
    public function woocommerce_email_classes($emails) {
        remove_all_actions( 'woocommerce_order_status_cancelled_to_completed_notification');
        remove_all_actions( 'woocommerce_order_status_cancelled_to_on-hold_notification');
        remove_all_actions( 'woocommerce_order_status_cancelled_to_processing_notification');
        remove_all_actions( 'woocommerce_order_status_completed_notification');
        remove_all_actions( 'woocommerce_order_status_failed_to_completed_notification');
        remove_all_actions( 'woocommerce_order_status_failed_to_on-hold_notification');
        remove_all_actions( 'woocommerce_order_status_failed_to_processing_notification');
        remove_all_actions( 'woocommerce_order_status_on-hold_to_cancelled_notification');
        remove_all_actions( 'woocommerce_order_status_on-hold_to_failed_notification');
        remove_all_actions( 'woocommerce_order_status_on-hold_to_processing_notification');
        remove_all_actions( 'woocommerce_order_status_pending_to_completed_notification');
        remove_all_actions( 'woocommerce_order_status_pending_to_failed_notification');
        remove_all_actions( 'woocommerce_order_status_pending_to_on-hold_notification');
        remove_all_actions( 'woocommerce_order_status_pending_to_processing_notification');
        remove_all_actions( 'woocommerce_order_status_processing_to_cancelled_notification');
        return [];
    }

    /**
     * Do not add system generated notes during import process.
     *
     * @param $note_data
     * @param $order_data
     *
     * @return array
     */
    public function woocommerce_new_order_note_data($note_data, $order_data) {
        return [];
    }

    /**
     *
     * After Import WooCommerce Order
     *
     * @return array
     */
    public function afterPostImport() {
	    $data_store = WC_Data_Store::load( 'customer-download' );
	    $data_store->delete_by_order_id( $this->getPid() );
	    wc_downloadable_product_permissions($this->getPid(), true);
        update_option('wp_all_import_previously_updated_order_' . $this->getImport()->id, $this->getPid(), false);
    }
}
