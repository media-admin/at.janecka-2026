window.germanized = window.germanized || {};

( function( $, germanized ) {

    /**
     * Order Data Panel
     */
    germanized.multistep_checkout = {

        params: {},

        init: function() {
            this.params  = wc_gzd_multistep_checkout_params;
            var self     = germanized.multistep_checkout,
                $wrapper = $( self.params.content_wrapper );

            // Support other Payment Plugins - just add a wrap around their custom payment wrapper
            if ( $wrapper.find( '#payment-manual' ).length ) {
                $wrapper.find( '#payment-manual' ).wrap( '<div id="order-payment"></div>' );
                $wrapper.find( '#order_payment_heading' ).insertBefore( '#payment-manual' );
            } else {
                $wrapper.find( '#payment' ).wrap( '<div id="order-payment"></div>' );
                $wrapper.find( '#order_payment_heading' ).insertBefore( '#payment' );
            }

            if (  $wrapper.find( '#order_review > #order-payment ~ *' ).length === 0 ) {
                $wrapper.find( '#order-payment ~ *' ).wrapAll( '<div id="order-verify"></div>' );
            } else {
                $wrapper.find( '#order_review > #order-payment ~ *' ).wrapAll( '<div id="order-verify"></div>' );
            }

            $wrapper.find( '#order_review_heading' ).prependTo( '#order-verify' );

            $.each( self.params.steps, function( index, elem ) {
                if ( $wrapper.find( elem.selector ).length )  {
                    // Wrap selector with step-wrapper
                    $wrapper.find( elem.selector ).wrap( '<div class="' + self.params.wrapper + ' ' + elem.wrapper_classes.join( ' ' ) +  '" id="step-wrapper-' + elem.id +  '" data-id="' + elem.id +  '"></div>' );

                    if ( elem.submit_html ) {
                        $wrapper.find( '#step-wrapper-' + elem.id ).append( elem.submit_html );
                    }
                }
            });

            $( '.step-wrapper' ).hide();

            $( 'body' ).trigger( 'wc_gzdp_multistep_checkout_init' );

            // Listen to AJAX Events to check whether fragments can be refreshed (data check within last step).
            $( document ).ajaxComplete( self.onAjaxComplete );

            /**
             * Inject step submit attribute before sending AJAX checkout request
             * for better third-party compatibility and to be sure to to track every form submit request.
             */
            $( document ).ajaxSend( self.onAjaxSend );

            $( document )
                .on( 'click', '.step, .step-trigger', self.onClickStep )
                .on( 'change', '.step', self.onChangeStep )
                .on( 'refresh', '.step-wrapper', self.onRefreshStep );

            $( document.body ).on( 'updated_checkout', self.onUpdateCheckout )

            /**
             * Add a hidden input field containing the current step to be submitted as a fallback
             * to make sure even requests (e.g. WooCommerce Payments) done without jQuery will transmit the correct step.
             */
            $( document )
                .on( 'click', '.next-step-button', self.beforeChangeStep )
                .on( 'click', '.prev-step-button', self.beforeChangeStep );

            // Trigger change on first step
            $( '.step-nav li a.step:first' ).trigger( 'change' );
        },

        onUpdateCheckout: function() {
            var self     = germanized.multistep_checkout,
                $wrapper = $( self.params.content_wrapper );

            /**
             * Force prepending the order review heading to the verify block.
             */
            $wrapper.find( '#order_review_heading' ).prependTo( '#order-verify' );
        },

        beforeChangeStep: function() {
            var self         = germanized.multistep_checkout,
                $currentStep = self.getCurrentStep();

            $( '#wc_gzdp_step_submit' ).remove();
            /**
             * Append to this wrapper as it gets refreshed/cleared after AJAX calls.
             */
            $( '.woocommerce-gzdp-checkout-verify-data' ).append( '<input id="wc_gzdp_step_submit" type="text" name="wc_gzdp_step_submit" value="' + $currentStep.data( 'id' ) + '" style="display: none" />' );
        },

        refreshCurrentStep: function( step ) {
            var self = germanized.multistep_checkout;

            $.post( self.params.ajax_url + '?action=woocommerce_gzdp_multistep_refresh_step', {
                wc_gzdp_multistep_refresh_step : self.params.refresh_step_nonce,
                step                           : step
            }, self.onRefreshCurrentStepSuccess, 'json' );
        },

        onRefreshCurrentStepSuccess: function() {},

        onClickNextStep: function( e ) {
            germanized.multistep_checkout.nextStep( e );
        },

        nextStep: function( e ) {
            var self = germanized.multistep_checkout;

            var $currentStep = self.getCurrentStep();
            var $button = $currentStep.find( '.next-step-button' );

            var next    = $button.data( 'next' ),
                current = $button.data( 'current' );

            if ( $button.parents( '.step-wrapper' ).hasClass( 'no-ajax' ) ) {
                $( '.step-' + next ).trigger( 'change', $( '.step-' + next ) );
                // Stop auto ajax reload
                e.preventDefault();
                e.stopPropagation();
            } else {
                $( document.body ).on( 'updated_checkout', function() {
                    if ( $( document ).find( '.woocommerce-checkout-payment .blockUI' ).length ) {
                        $( document ).find( '.woocommerce-checkout-payment' ).unblock();
                    }
                });

                // Trigger Wrapper Refresh
                $button.parents( '.step-wrapper' ).trigger( 'refresh' );

                $( 'body' ).on( 'wc_gzdp_step_refreshed', function() {
                    if ( ! self.checkoutHasErrors() ) {
                        // next step
                        $( '.step-' + next ).trigger( 'change', $( '.step-' + next ) );
                    }

                    $( 'body' ).off( 'wc_gzdp_step_refreshed' );
                });
            }
        },

        checkoutHasErrors: function() {
            var hasError = false,
                $errorWrapper = $( '.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout' ).find( '.woocommerce-error, .is-error' );

            /**
             * Explicitly check whether the error wrapper exists and has children.
             * Some payment plugins might treat the failure response created by the AJAX
             * step response manually and try to add (empty) error messages returned by the request via JS.
             */
            if ( $errorWrapper.length > 0 && ( $errorWrapper.children().length > 0 || $errorWrapper.text().trim() ) ) {
                hasError = true;

                /**
                 * Some payment plugins e.g. Stripe do not remove the child-elements (e.g. li)
                 * but leave empty orphan elements. Explicitly check the li content and prevent error state.
                 */
                if ( $errorWrapper.find( 'li' ).length > 0 ) {
                    var error_text = $.trim( $errorWrapper.find( 'li' ).text() );

                    if ( '' === error_text ) {
                        hasError = false;
                    }
                }
            }

            return hasError;
        },

        onRefreshStep: function() {
            if ( $( this ).find( '.step-buttons' ).length ) {
                $( this ).find( '.step-buttons' ).prepend( '<input type="hidden" id="wc-gzdp-step-submit" name="wc_gzdp_step_submit" value="' + $( this ).data( 'id' ) + '" />' );

                $( 'body' ).on( 'checkout_error.multistep_checkout', function( e ) {
                    $( '#wc-gzdp-step-submit' ).remove();

                    $( 'body' ).trigger( 'wc_gzdp_step_refreshed' );
                    $( 'body' ).off( 'checkout_error.multistep_checkout' );
                });
            }
        },

        onChangeStep: function( e, elem ) {
            var id   = $( this ).data( 'href' ),
                self = germanized.multistep_checkout;

            self.refreshCurrentStep( id );
            $( '#wc_gzdp_step_submit' ).remove();

            if ( $( '#step-wrapper-' + id ).length ) {
                if ( elem !== undefined ) {
                    $( '.woocommerce-error' ).remove();
                }

                $( '.step-nav' ).find( '.active' ).removeClass( 'active' );
                $( this ).parents( 'li' ).addClass( 'active' );

                $( this ).attr( 'href', '#step-' + $( this ).data( 'href' ) );

                $( '.step-wrapper' ).hide();
                $( '.step-wrapper' ).removeClass( 'step-wrapper-active' );
                $( '#step-wrapper-' + id ).show();
                $( '#step-wrapper-' + id ).addClass( 'step-wrapper-active' );

                /**
                 * Remove invalid classes from last-step elements
                 * to prevent (e.g. checkboxes) from being marked as invalid without submitting.
                 */
                $( '#order-verify' ).find( '.woocommerce-invalid' ).removeClass( 'woocommerce-invalid woocommerce-invalid-required-field' );

                $( 'body' ).removeClass( function ( index, className ) {
                    return ( className.match( /(^|\s)woocommerce-multistep-checkout-active-\S+/g ) || [] ).join(' ');
                });

                window.location.hash = 'step-' + id;

                $( 'body' ).addClass( 'woocommerce-multistep-checkout-active-' + id );

                self.scroll_to_notices();

                $( 'body' ).trigger( 'wc_gzdp_step_changed', $( this ) );
            }
        },

        scroll_to_notices: function () {
            var scrollElement = $(
                '.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout'
            );

            if ( ! scrollElement.length ) {
                scrollElement = $( 'form.checkout' );
            }

            $.scroll_to_notices( scrollElement );
        },

        onClickStep: function() {
            if ( ! $( this ).attr( 'href' ) ) {
                return false;
            }

            var step = $( this ).data( 'href' );

            $( 'body' ).trigger( 'wc_gzdp_show_step', $( this ) );
            $( '.step-' + step ).trigger( 'change', $( this ) );
        },

        getCheckoutUrl: function() {
            return wc_checkout_params.checkout_url;
        },

        onAjaxSend: function( ev, jqXHR, settings ) {
            var self = germanized.multistep_checkout;

            if ( self.getCheckoutUrl() === settings.url ) {
                self.beforeSubmitCheckout( ev, settings );
            }
        },

        beforeSubmitCheckout: function( ev, settings ) {
            var self     = germanized.multistep_checkout,
                $current = self.getCurrentStep();

            if ( 'order' !== $current.data( 'id' ) ) {
                /**
                 * Plugins, e.g. WooCommerce Payments may use fetch with a custom FormData object.
                 */
                if ( settings.data instanceof FormData ) {
                    settings.data.append( 'wc_gzdp_step_submit', $current.data( 'id' ) );
                } else {
                    settings.data += '&wc_gzdp_step_submit=' + $current.data( 'id' );
                }

                germanized.multistep_checkout.nextStep( ev );
            }
        },

        getCurrentStep: function() {
            return $( '.step-wrapper-active' ).length > 0 ? $( '.step-wrapper-active' ) : $( '.step-wrapper-1' );
        },

        init_payment_methods: function() {
            var $payment_methods = $( '.woocommerce-checkout' ).find( 'input[name="payment_method"]' );

            // If there is one method, we can hide the radio input
            if ( 1 === $payment_methods.length ) {
                $payment_methods.eq(0).hide();
            }

            // If there are none selected, select the first.
            if ( 0 === $payment_methods.filter( ':checked' ).length ) {
                $payment_methods.eq(0).prop( 'checked', true );
            }

            // Get name of new selected method.
            var checkedPaymentMethod = $payment_methods.filter( ':checked' ).eq(0).prop( 'id' );

            if ( $payment_methods.length > 1 ) {
                // Hide open descriptions.
                $( 'div.payment_box:not(".' + checkedPaymentMethod + '")' ).filter( ':visible' ).slideUp( 0 );
            }

            // Trigger click event for selected method
            $payment_methods.filter( ':checked' ).eq(0).trigger( 'click' );
        },

        onRefreshFragments: function( response ) {
            var self = germanized.multistep_checkout;

            // Check if fragment exists in object
            if ( response.fragments.hasOwnProperty( '.woocommerce-gzdp-checkout-verify-data' ) ) {
                $( '.woocommerce-gzdp-checkout-verify-data' ).replaceWith( response.fragments['.woocommerce-gzdp-checkout-verify-data'] );
            }

            if ( response.fragments.hasOwnProperty( '.woocommerce-checkout-payment' ) && response.fragments.hasOwnProperty( 'wc-gzdp-payment-wrap-needs-init' ) ) {
                $( '.woocommerce-checkout-payment' ).replaceWith( response.fragments['.woocommerce-checkout-payment'] );

                self.init_payment_methods();
                $( document.body ).trigger( 'updated_checkout' );
            }

            if ( response.fragments.hasOwnProperty( '.step-nav' ) ) {
                $( '.step-nav' ).replaceWith( response.fragments['.step-nav'] );

                $( 'ul.step-nav li a' ).each( function() {
                    var id = $( this ).data( 'href' );

                    if ( response.fragments.hasOwnProperty( '.step-buttons-' + id ) ) {
                        $( '.step-buttons-' + id ).replaceWith( response.fragments['.step-buttons-' + id] );
                    }
                });
            }
        },

        getUpdateOrderReviewUrl: function () {
            return wc_checkout_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'update_order_review' );
        },

        onAjaxComplete: function( ev, jqXHR, settings ) {
            var self = germanized.multistep_checkout;

            if ( ( settings.url === self.getUpdateOrderReviewUrl() || settings.url === self.getCheckoutUrl() ) && jqXHR != null && jqXHR.hasOwnProperty( 'responseText' ) ) {
                var response = null;

                try {
                    response = JSON.parse( jqXHR.responseText );
                } catch ( error ) {
                    response = null;
                }

                if ( response !== null && response.hasOwnProperty( 'fragments' ) ) {
                    self.onRefreshFragments( response );
                }
            }
        }
    };

    $( document ).ready( function() {
        germanized.multistep_checkout.init();

        // Override scroll
        $.scroll_to_notices = function ( scrollElement ) {
            var $customScrollElement = $( '#woocommerce-multipstep-checkout-custom-scroll-marker' );

            if ( $customScrollElement.length > 0 ) {
                scrollElement = $customScrollElement;
            }

            if ( scrollElement.length ) {
                $( 'html, body' ).animate(
                    {
                        scrollTop: scrollElement.offset().top - 100,
                    },
                    1000
                );
            }
        };
    });

})( jQuery, window.germanized );