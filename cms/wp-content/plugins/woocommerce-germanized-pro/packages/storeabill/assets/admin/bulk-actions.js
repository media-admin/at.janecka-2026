window.storeabill = window.storeabill || {};
window.storeabill.admin = window.storeabill.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.bulk_actions = {

        params: {},

        init: function() {
            var self        = storeabill.admin.bulk_actions;
            self.params     = storeabill_admin_bulk_actions_params;

            $( document )
                .on( 'click', '#doaction, #doaction2', self.onBulkSubmit );
        },

        getCurrentSort: function( column ) {
            var self = storeabill.admin.bulk_actions,
                sorted = 'desc',
                $form  = self.getForm(),
                columnIdentifier = column;

            var $thead = $form.find( 'table.wp-list-table thead' );
            var $th    = $thead.find( "th[class*='" + columnIdentifier + "']" ).first();

            if ( $th.length > 0 ) {
                if ( $th.hasClass( 'asc' ) ) {
                    sorted = 'asc';
                }
            }

            return sorted;
        },

        onBulkSubmit: function() {
            var self   = storeabill.admin.bulk_actions,
                action = $( this ).parents( '.bulkactions' ).find( 'select[name^=action]' ).val(),
                $filterForm = $( this ).parents( '#posts-filter' ).length > 0 ? $( this ).parents( '#posts-filter' ) : $( this ).parents( '#wc-orders-filter' ),
                type   = self.params.hasOwnProperty( 'object_type' ) ? self.params.object_type : $filterForm.find( 'input[name=' + self.params.object_input_type_name + ']' ).val(),
                ids    = [];

            self.getForm().find( 'input[name="' + self.getInputIdName() + '[]"]:checked' ).each( function() {
                ids.push( $( this ).val() );
            });

            if ( self.params.bulk_actions.hasOwnProperty( action ) && ids.length > 0 ) {
                var actionData = self.params.bulk_actions[ action ];
                var sort= self.getCurrentSort( actionData['id_order_by_column'] );

                /**
                 * In case ids are sorted descending - reverse array in case
                 * this bulk handler expects ids to be parsed in an ascending order
                 */
                if ( 'desc' === sort && actionData['parse_ids_ascending'] ) {
                    ids = ids.reverse();
                }

                $( '.sab-bulk-action-wrapper' ).find( '.bulk-title' ).text( actionData['title'] );
                $( '.sab-bulk-action-wrapper' ).addClass( 'processing' );
                $( '.sab-bulk-action-wrapper' ).parents( '.tablenav' ).addClass( 'sab-bulk-action-running' );

                self.getForm().find( '.bulkactions button' ).prop( 'disabled', true ).addClass( 'disabled' );

                // Handle bulk action processing
                self.handleBulkAction( action, 1, ids, type );

                return false;
            }
        },

        getInputIdName: function() {
            var self = storeabill.admin.bulk_actions;

            if ( $( 'input[name="' + self.params.table_type + '[]"]' ).length > 0 ) {
                return self.params.table_type;
            } else {
                return 'id';
            }
        },

        getForm: function() {
            var self = storeabill.admin.bulk_actions;

            return $( 'input[name="' + self.getInputIdName() + '[]"]:checked' ).parents( 'form' );
        },

        handleBulkAction: function( action, step, ids, type ) {
            var self       = storeabill.admin.bulk_actions,
                actionData = self.params.bulk_actions[ action ];

            $.ajax( {
                type: 'POST',
                url: self.params.ajax_url,
                data: {
                    action           : 'storeabill_admin_handle_bulk_action',
                    bulk_action      : action,
                    step             : step,
                    type             : type,
                    referer          : window.location.href,
                    reference_type   : self.params.hasOwnProperty( 'reference_type' ) ? self.params.reference_type : '',
                    ids              : ids,
                    security         : actionData['nonce']
                },
                dataType: 'json',
                success: function( response ) {
                    if ( response.success ) {
                        if ( 'done' === response.step ) {
                            $( '.sab-bulk-action-wrapper' ).find( '.sab-bulk-progress' ).val( response.percentage );

                            window.location = response.url;

                            setTimeout( function() {
                                $( '.sab-bulk-action-wrapper' ).removeClass( 'processing' );
                                $( '.sab-bulk-action-wrapper' ).parents( '.tablenav' ).removeClass( 'sab-bulk-action-running' );

                                self.getForm().find( '.bulkactions button' ).prop( 'disabled', false ).removeClass( 'disabled' );
                            }, 2000 );
                        } else {
                            $( '.sab-bulk-action-wrapper' ).find( '.sab-bulk-progress' ).val( response.percentage );

                            self.handleBulkAction( action, parseInt( response.step, 10 ), response.ids, response.type );
                        }
                    } else {
                        $( '.sab-bulk-notice-wrapper' ).find( '.notice' ).remove();
                        $( '.sab-bulk-action-wrapper' ).removeClass( 'processing' );
                        $( '.sab-bulk-action-wrapper' ).parents( '.tablenav' ).removeClass( 'sab-bulk-action-running' );

                        self.getForm().find( '.bulkactions button' ).prop( 'disabled', false ).removeClass( 'disabled' );

                        if ( response.hasOwnProperty( 'messages' ) ) {
                            $.each( response.messages, function( i, message ) {
                                $( '.sab-bulk-notice-wrapper' ).append( '<div class="notice is-dismissible notice-error"><p>' + message + '</p><button type="button" class="notice-dismiss"></button></div>' );
                            });
                        }
                    }
                }
            }).fail( function( response ) {
                window.console.log( response );
            } );
        },
    };

    $( document ).ready( function() {
        storeabill.admin.bulk_actions.init();
    });

})( jQuery, window.storeabill.admin );
