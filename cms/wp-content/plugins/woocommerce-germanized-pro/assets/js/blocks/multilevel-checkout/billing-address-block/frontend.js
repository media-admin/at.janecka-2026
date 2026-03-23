import classnames from 'classnames';
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { useSelect, useDispatch, select } from '@wordpress/data';
import { _x } from '@wordpress/i18n';
import { CHECKOUT_STORE_KEY, CART_STORE_KEY } from '@woocommerce/block-data';
import { getSetting } from '@germanizedpro/settings';

import RadioControlAccordion from  "@germanizedpro/base-components/radio-control-accordion";
import { getSelectedShippingMethod, rateHasPickup } from "@germanizedpro/base-utils/shipping";
import { useMultilevelCheckoutDataContext } from "../data";

export const Frontend = ({
  children,
  className,
}) => {
    const {
        needsShipping,
        currentStep
    } = useMultilevelCheckoutDataContext();

    const { __internalSetUseShippingAsBilling } = useDispatch( CHECKOUT_STORE_KEY );
    const [ showBillingAddress, setShowBillingAddress ] = useState( false );
    const contentRef = useRef( null );
    const hasForcedBillingAddress = getSetting( 'forcedBillingAddress' );

    const {
        shippingRates
    } = useSelect((select) => {
        const store = select( CART_STORE_KEY );

        return {
            shippingRates: store.getShippingRates()
        }
    });

    const selected = getSelectedShippingMethod( shippingRates );
    const hasPickup = useMemo(
        () => selected && rateHasPickup( selected ),
        [ selected ]
    );

    useEffect( () => {
        if ( ! needsShipping || hasForcedBillingAddress || hasPickup ) {
            if ( 'contact' === currentStep ) {
                setShowBillingAddress( true );
            } else {
                setShowBillingAddress( false );
            }
        }
    }, [
        needsShipping,
        setShowBillingAddress,
        currentStep,
        hasForcedBillingAddress,
        hasPickup
    ] );

    const options = [
        {
            value: 'identical',
            label: _x( 'Identical with shipping address', 'multilevel-checkout', 'woocommerce-germanized-pro' ),
            content: '',
        },
        {
            value: 'different',
            label: _x( 'Use a separate billing address', 'multilevel-checkout', 'woocommerce-germanized-pro' ),
            content: children,
        },
    ];

    return (
        <>
            { 'payment' === currentStep && needsShipping && ! hasForcedBillingAddress && ! hasPickup ?
                <fieldset className="wc-gzdp-block-multilevel-checkout__billing-address wc-block-components-checkout-step wc-block-components-checkout-step--with-step-number">
                    <div className="wc-block-components-checkout-step__heading">
                        <h2 className="wc-block-components-title wc-block-components-checkout-step__title">{ _x( 'Billing address', 'multilevel-checkout', 'woocommerce-germanized-pro' ) }</h2>
                    </div>
                    <div className="wc-block-components-checkout-step__container">
                        <p className="wc-block-components-checkout-step__description">{ _x( 'Optionally choose a differing billing address that matches your payment method.', 'multilevel-checkout', 'woocommerce-germanized-pro' ) }</p>
                        <div className="wc-block-components-checkout-step__content" ref={ contentRef }>
                            <RadioControlAccordion
                                id={'wc-gzdp-use-separate-billing-address' }
                                selected={ showBillingAddress ? 'different' : 'identical' }
                                onChange={ ( value ) => {
                                    if ( 'identical' === value ) {
                                        __internalSetUseShippingAsBilling( true );
                                        setShowBillingAddress( false );
                                    } else {
                                        __internalSetUseShippingAsBilling( false );
                                        setShowBillingAddress( true );

                                        if ( contentRef.current !== null && ! contentRef.current.classList.contains( 'is-editing' ) ) {
                                            window.setTimeout( () => {
                                                const editLink = contentRef.current.querySelector( '.wc-block-components-address-card__edit' );

                                                if ( editLink ) {
                                                    editLink.click();
                                                }
                                            }, 50 );
                                        }
                                    }
                                } }
                                options={ options }
                            />
                        </div>
                    </div>
                </fieldset>
                : showBillingAddress ? children : []
            }
        </>
    );
};

export default Frontend;
