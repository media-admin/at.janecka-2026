import classnames from 'classnames';
import { useMultilevelCheckoutDataContext } from "../data"
import { _x, sprintf } from "@wordpress/i18n";
import {
    createContext,
    useContext,
    useReducer,
    useEffect,
    useMemo,
    useRef,
    useCallback,
    cloneElement
} from '@wordpress/element';
import { useDispatch, useSelect, select, dispatch } from '@wordpress/data';

import { CART_STORE_KEY, CHECKOUT_STORE_KEY, VALIDATION_STORE_KEY, PAYMENT_STORE_KEY } from '@woocommerce/block-data';
import { getSetting } from '@woocommerce/settings';
import FormattedMonetaryAmount from "@germanizedpro/base-components/formatted-monetary-amount";
import { getCurrencyFromPriceResponse } from "@germanizedpro/base-utils/currency";
import { getSelectedShippingMethod } from "@germanizedpro/base-utils/shipping";
import { decodeEntities } from '@wordpress/html-entities';
import { isString, objectHasProp } from '@germanizedpro/types';
import { PaymentMethodLabel } from "../payment-method-label";
import { PaymentMethodIcons } from "../payment-method-icons";

import { getPaymentMethods } from '@woocommerce/blocks-registry';

const countries = getSetting( 'countries', {} );
const countryData = getSetting( 'countryData', {} );
const ALLOWED_COUNTRIES = Object.fromEntries(
    Object.keys( countryData )
        .filter( ( countryCode ) => {
            return countryData[ countryCode ].allowBilling === true;
        } )
        .map( ( countryCode ) => {
            return [ countryCode, countries[ countryCode ] || '' ];
        } )
);

const FormattedAddress = ({
    address
}) => {
    return (
        <address>
            {[
                address.first_name + ' ' + address.last_name,
                address.address_1,
                address.address_2,
                address.postcode + ' ' + address.city,
                address.state,
                ALLOWED_COUNTRIES[address.country]
                    ? ALLOWED_COUNTRIES[address.country]
                    : address.country,
            ]
                .filter((field) => !!field)
                .map((field, index) => (
                    <span key={`address-` + index}>{field}</span>
                ))}
        </address>
    );
};

const ContactSummary = () => {
    const {
        billingAddress,
        needsShipping,
        shippingAddress
    } = useSelect((select) => {
        const store = select(CART_STORE_KEY);

        return {
            billingAddress: store.getCartData().billingAddress,
            shippingAddress: store.getCartData().shippingAddress,
            needsShipping: store.getNeedsShipping(),
        }
    });

    return (
        <div className="summary-content">
            <span className="summary-main-content">{billingAddress.email}</span>
            { !needsShipping &&
                <div className="billing-address">
                    <FormattedAddress address={ billingAddress } />
                </div>
            }
            { needsShipping &&
                <div className="shipping-address">
                    <FormattedAddress address={ shippingAddress } />
                </div>
            }
        </div>
    );
};

const PaymentSummary = () => {
    const {
        billingAddress,
        currentPaymentMethod,
        useShippingAsBilling,
        needsShipping,
        availablePaymentMethods
    } = useSelect((select) => {
        const store = select(CART_STORE_KEY);
        const paymentStore = select(PAYMENT_STORE_KEY);
        const checkoutStore = select( CHECKOUT_STORE_KEY );

        return {
            billingAddress: store.getCartData().billingAddress,
            currentPaymentMethod: paymentStore.getActivePaymentMethod(),
            useShippingAsBilling: checkoutStore.getUseShippingAsBilling(),
            needsShipping: store.getNeedsShipping(),
            availableExpressPaymentMethods: paymentStore.getAvailableExpressPaymentMethods(),
            availablePaymentMethods: paymentStore.getAvailablePaymentMethods(),
        }
    } );

    const paymentMethods = getPaymentMethods();
    let currentPaymentMethodLabel = '';

    Object.keys( availablePaymentMethods ).map( ( name ) => {
        const { label } = paymentMethods[ name ];

        if ( name === currentPaymentMethod ) {
            currentPaymentMethodLabel = typeof label === 'string'
                ? label
                : cloneElement( label, {
                    components: {
                        PaymentMethodLabel,
                        PaymentMethodIcons
                    },
                } );
        }
    } );

    return (
        <div className="summary-content">
            <span className="summary-main-content">{currentPaymentMethodLabel}</span>

                {!useShippingAsBilling && needsShipping &&
                <div className="billing-address">
                    <FormattedAddress address={billingAddress}/>
                </div>
            }
        </div>
    );
};

const ShippingSummary = () => {
    const {
        shippingAddress,
        shippingRates
    } = useSelect((select) => {
        const store = select(CART_STORE_KEY);

        return {
            shippingAddress: store.getCartData().shippingAddress,
            shippingRates: store.getShippingRates()
        }
    });

    const selected = getSelectedShippingMethod( shippingRates );

    if ( ! selected ) {
        return null;
    }

    const priceWithTaxes = getSetting( 'displayCartPricesIncludingTax', false )
        ? parseInt( selected.price, 10 ) + parseInt( selected.taxes, 10 )
        : parseInt( selected.price, 10 );

    return (
        <div className="summary-content">
            <span className="summary-main-content shipping-method-name">
                { decodeEntities( selected.name ) }
            </span>&nbsp;
            <span className="shipping-method-price">
                <FormattedMonetaryAmount
                    currency={ getCurrencyFromPriceResponse( selected ) }
                    value={ priceWithTaxes }
                />
            </span>
        </div>
    );
};

export const Summary = () => {
    const {
        getStepTitle,
        currentStep,
        availableSteps,
        onChangeStep
    } = useMultilevelCheckoutDataContext();

    const summarySteps = [];

    for ( let i = 0; i < availableSteps.length; i++ ) {
        if ( currentStep === availableSteps[i] ) {
            break;
        }

        summarySteps.push( availableSteps[i] );
    }

    if ( summarySteps.length <= 0 ) {
        return null;
    }

    return (
        <div className="wc-gzdp-multilevel-checkout-step-summary">
            { summarySteps.map( ( stepId ) => {
                return (
                    <div
                        className={ classnames(
                            'summary-block-item',
                            `summary-block-item-${ stepId }`
                        ) }
                        key={ stepId }>
                        <div className="summary-inner-wrapper">
                            <div className="summary-header">{ getStepTitle( stepId ) }</div>
                            { 'contact' === stepId &&
                                <ContactSummary />
                            }
                            { 'shipping' === stepId &&
                                <ShippingSummary />
                            }
                            { 'payment' === stepId &&
                                <PaymentSummary />
                            }
                        </div>
                        <div className="summary-edit">
                            <a href="#" onClick={ () => onChangeStep( stepId ) }>{ _x( 'Edit', 'multilevel-checkout', 'woocommerce-germanized-pro' ) }</a>
                        </div>
                    </div>
                )
            })}
        </div>
    );
};

export default Summary;
