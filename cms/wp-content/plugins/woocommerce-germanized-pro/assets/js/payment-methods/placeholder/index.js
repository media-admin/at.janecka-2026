/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { getPaymentMethods } from '@woocommerce/blocks-registry';
import { cloneElement } from "@wordpress/element";
import { PaymentMethodLabel } from "../../blocks/multilevel-checkout/payment-method-label";
import { PaymentMethodIcons } from "../../blocks/multilevel-checkout/payment-method-icons";

const paymentMethods = getSetting( 'paymentMethodData', {} );

Object.entries( paymentMethods ).forEach( ( [ methodName, method ] ) => {
    if ( 'placeholder_' !== methodName.slice( 0, 12 ) ) {
        return;
    }

    const label = decodeEntities( method?.title || '' );
    const originalName = methodName.slice( 12 );

    /**
     * Content component
     */
    const Content = () => {
        return decodeEntities( method.description || '' );
    };

    const getOriginalGateway = () => {
        const paymentMethods = getPaymentMethods();
        let originalGateway = false;

        Object.keys( paymentMethods ).map( ( name ) => {
            if ( name === originalName ) {
                return originalGateway = paymentMethods[ name ];
            }
        } );

        return originalGateway;
    };

    /**
     * Label component
     *
     * @param {*} props Props from payment API.
     */
    const Label = ( props ) => {
        const { PaymentMethodLabel } = props.components;
        let currentPaymentMethodLabel = <PaymentMethodLabel text={ label } />;
        let originalGateway = getOriginalGateway();

        if ( originalGateway ) {
            const { label } = originalGateway;

            currentPaymentMethodLabel = typeof label === 'string'
                ? label
                : cloneElement( label, {
                    components: {
                        PaymentMethodLabel,
                        PaymentMethodIcons
                    },
                } );
        }

        return currentPaymentMethodLabel;
    };

    const PlaceholderMethod = {
        name: methodName,
        label: <Label />,
        content: <Content />,
        edit: <Content />,
        canMakePayment: ( cart ) => {
            let originalGateway = getOriginalGateway();

            /**
             * If we cannot find the original gateway, seems like it has not been registered and is not available for this cart.
             */
            if ( ! originalGateway ) {
                return false;
            }

            return originalGateway.canMakePayment( cart );
        },
        ariaLabel: label,
        supports: {
            features: method?.supports ?? [],
        },
    };

    registerPaymentMethod( PlaceholderMethod );
} );
