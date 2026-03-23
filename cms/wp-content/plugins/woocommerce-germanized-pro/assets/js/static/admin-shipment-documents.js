window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};
window.shiptastic = window.shiptastic || {};
window.shiptastic.admin = window.shiptastic.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.shipment_documents = {

        params: {},

        init: function () {
            var self    = germanized.admin.shipment_documents;
            self.params = wc_gzdp_admin_shipment_documents_params;

            $( document )
                .on( 'click', '#panel-order-shipments .create-packing-slip:not(.disabled)', self.onCreatePackingSlip )
                .on( 'click', '#panel-order-shipments .remove-packing-slip', self.onRemovePackingSlip )
                .on( 'click', '#panel-order-shipments .remove-commercial-invoice', self.onRemoveCommercialInvoice );

            $( document.body )
                .on( 'woocommerce_stc_shipments_needs_saving', self.onShipmentsNeedsSavingChange );
        },

        onShipmentsNeedsSavingChange: function( e, needsSaving, currentShipmentId ) {
            var self      = germanized.admin.shipment_documents,
                $shipment = self.getShipment( currentShipmentId );

            if ( needsSaving ) {
                self.disableCreatePackingSlip( $shipment );
            } else {
                self.enableCreatePackingSlip( $shipment );
            }
        },

        disableCreatePackingSlip: function( $shipment ) {
            var self    = germanized.admin.shipment_documents,
                $button = $shipment.find( '.create-packing-slip' );

            $button.addClass( 'disabled button-disabled' );
            $button.prop( 'title', self.params.i18n_create_packing_slip_disabled );

            // Tooltips
            $( document.body ).trigger( 'init_tooltips' );
        },

        enableCreatePackingSlip: function( $shipment ) {
            var self    = germanized.admin.shipment_documents,
                $button = $shipment.find( '.create-packing-slip' );

            $button.removeClass( 'disabled button-disabled' );
            $button.prop( 'title', self.params.i18n_create_packing_slip_enabled );

            // Tooltips
            $( document.body ).trigger( 'init_tooltips' );
        },

        getMainWrapper: function() {
            return $( '#order-shipments' );
        },

        getPackingSlipWrapper: function( packingSlipId ) {
            var self = germanized.admin.shipment_documents;

            return self.getMainWrapper().find( '.wc-stc-shipment-packing-slip[data-packing_slip="' + packingSlipId + '"]' );
        },

        getShipmentWrapperByPackingSlip: function( packingSlipId ) {
            var self       = germanized.admin.shipment_documents,
                $wrapper   = self.getPackingSlipWrapper( packingSlipId );

            if ( $wrapper.length > 0 ) {
                return $wrapper.parents( '.order-shipment' );
            }

            return false;
        },

        blockPackingSlip: function( $shipment ) {
            $wrapper = $shipment.find( '.wc-stc-shipment-packing-slip' );

            $wrapper.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        },

        unblockPackingSlip: function() {
            $wrapper = $( this ).find( '.wc-stc-shipment-packing-slip' );

            $wrapper.unblock();
        },

        removePackingSlip: function( packingSlipId ) {
            var self = germanized.admin.shipment_documents,
                $shipment = self.getShipmentWrapperByPackingSlip( packingSlipId );

            var params = {
                'action'      : 'woocommerce_gzdp_remove_packing_slip',
                'packing_slip': packingSlipId,
                'security'    : self.params.remove_packing_slip_nonce
            };

            self.blockPackingSlip( $shipment );
            self.getShipmentsModule().doAjax( params, null, self.unblockPackingSlip );
        },

        blockCommercialInvoice: function( $shipment ) {
            $wrapper = $shipment.find( '.wc-gzdp-shipment-commercial-invoice' );

            $wrapper.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        },

        unblockCommercialInvoice: function() {
            $wrapper = $( this ).find( '.wc-gzdp-shipment-commercial-invoice' );

            $wrapper.unblock();
        },

        removeCommercialInvoice: function( commercialInvoiceId, $shipment ) {
            var self = germanized.admin.shipment_documents;

            var params = {
                'action'            : 'woocommerce_gzdp_remove_commercial_invoice',
                'commercial_invoice': commercialInvoiceId,
                'security'          : self.params.remove_commercial_invoice_nonce
            };

            self.blockCommercialInvoice( $shipment );
            self.getShipmentsModule().doAjax( params, null, self.unblockCommercialInvoice );
        },

        getShipmentsModule: function() {
            return shiptastic.admin.shipments;
        },

        onRemoveCommercialInvoice: function() {
            var self                = germanized.admin.shipment_documents,
                commercialInvoiceId = $( this ).data( 'commercial_invoice' ),
                $shipment           = $( this ).parents( '.order-shipment' );

            var answer = window.confirm( self.params.i18n_remove_commercial_invoice_notice );

            if ( answer ) {
                self.removeCommercialInvoice( commercialInvoiceId, $shipment );
            }

            return false;
        },

        onRemovePackingSlip: function() {
            var self          = germanized.admin.shipment_documents,
                packingSlipId = $( this ).data( 'packing_slip' );

            var answer = window.confirm( self.params.i18n_remove_packing_slip_notice );

            if ( answer ) {
                self.removePackingSlip( packingSlipId );
            }

            return false;
        },

        getShipment: function( id ) {
            return $( '#panel-order-shipments' ).find( '#shipment-' + id );
        },

        onCreatePackingSlip: function() {
            var self       = germanized.admin.shipment_documents,
                shipmentId = $( this ).parents( '.order-shipment' ).data( 'shipment' );

            self.refreshPackingSlip( shipmentId );

            return false;
        },

        refreshPackingSlip: function( shipmentId ) {
            var self      = germanized.admin.shipment_documents,
                $shipment = self.getShipment( shipmentId );

            var params = {
                'action'      : 'woocommerce_gzdp_refresh_packing_slip',
                'shipment_id' : shipmentId,
                'security'    : self.params.refresh_packing_slip_nonce
            };

            self.blockPackingSlip( $shipment );
            self.getShipmentsModule().doAjax( params, null, self.unblockPackingSlip );
        }
    };

    $( document ).ready( function() {
        germanized.admin.shipment_documents.init();
    });

})( jQuery, window.germanized.admin );
