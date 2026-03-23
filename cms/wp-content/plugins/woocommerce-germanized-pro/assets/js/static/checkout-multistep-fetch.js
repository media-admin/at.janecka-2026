window.germanized = window.germanized || {};

( function( $, germanized ) {
    germanized.multistep_checkout_fetch = {
        init: function() {
            window.originalFetch = fetch;

            /**
             * Override the global fetch method to make sure we can simulate
             * ajaxSend and ajaxComplete jQuery-style events.
             */
            fetch = this.fetch;
        },

        fetch: function ( url, init ) {
            if ( germanized.multistep_checkout ) {
                if ( url === germanized.multistep_checkout.getCheckoutUrl() ) {
                    var e = $.Event( "ajaxSend" );
                    var settings = {
                        'url': url,
                        'data': init.body
                    }

                    germanized.multistep_checkout.beforeSubmitCheckout( e, settings );

                    /**
                     * Explicitly override the manipulated data object to prevent data losses as
                     * observed in some environments.
                     */
                    init.body = settings.data;
                }
            }

            return window.originalFetch( url, init ).then( function( response ) {
                /**
                 * Create a new promise and await the existing one.
                 */
                return new Promise( function( resolve ) {
                    response.clone() // clone to allow invoking .json() multiple times
                    .text() // cannot use .json() here as Woo checkout sometimes uses html responses, e.g. during coupons
                    .then( function( data ) {
                        if ( germanized.multistep_checkout && ( url === germanized.multistep_checkout.getUpdateOrderReviewUrl() || url === germanized.multistep_checkout.getCheckoutUrl() ) ) {
                            json = null;

                            try {
                                json = JSON.parse( data );
                            } catch ( error ) {
                                json = null;
                            }

                            if ( json !== null && json.hasOwnProperty( 'fragments' ) ) {
                                germanized.multistep_checkout.onRefreshFragments( json );
                            }
                        }

                        resolve( response );
                    });
                });
            });
        }
    };

    $( document ).ready( function() {
        germanized.multistep_checkout_fetch.init();
    });
})( jQuery, window.germanized );