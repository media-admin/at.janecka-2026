import { useEffect, useRef, useState, useCallback } from '@wordpress/element';
import { extensionCartUpdate } from '@woocommerce/blocks-checkout';
import { getSetting } from '@germanizedpro/settings';
import { useSelect, useDispatch, select } from '@wordpress/data';
import { debounce } from "@germanizedpro/base-utils/debounce";
import { CART_STORE_KEY, CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import triggerFetch from '@wordpress/api-fetch';
import { __ } from "@wordpress/i18n";
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import {
    Spinner,
} from "@germanizedpro/base-components/spinner";

import {
    ValidatedTextInput
} from '@woocommerce/blocks-checkout';

const isPostCodeVatExempt = ( country, postcode ) => {
    const exempts = getSetting( 'postcodeVatExempts' );
    let isExempt = false;

    if ( exempts.hasOwnProperty( country ) ) {
        exempts[ country ].forEach( ( exemptPostcode ) => {
            const re = new RegExp( '^' + exemptPostcode.replace( '*', '(.*)' ) );

            if ( re.test( postcode ) ) {
                isExempt = true;
            }
        } );
    } else if ( 'GB' === country ) {
        isExempt = true;

        const re = /^BT(.*)/;

        if ( re.test( postcode ) ) {
            isExempt = false;
        }
    }

    return isExempt;
};

const getVatIdLocaleField = ( country ) => {
    const countryData        = getSetting( 'countryData', {} );
    const currentCountryData = countryData[ country ];

    if ( currentCountryData && currentCountryData.locale && currentCountryData.locale.hasOwnProperty( 'vat_id' ) ) {
        return currentCountryData.locale.vat_id;
    }

    return null;
};

const showVatIdField = ( country, postcode, addressType ) => {
    const locale = getVatIdLocaleField( country );
    const postCodeError = useSelect( ( select ) => {
        return select( VALIDATION_STORE_KEY ).getValidationError(
            `${ addressType }_postcode`
        );
    } );

    if ( ( locale && locale.hidden ) || isPostCodeVatExempt( country, postcode ) || ! postcode || postCodeError ) {
        return false;
    }

    return true;
};

const vatIdIsRequired = ( country, postcode ) => {
    const locale = getVatIdLocaleField( country );
    let isRequired = false;

    if ( locale ) {
        isRequired = ! locale.hidden && locale.required;
    }

    if ( isPostCodeVatExempt( country, postcode ) ) {
        isRequired = false;
    }

    return isRequired;
};

const localState = {
    // True when a push is currently happening to avoid simultaneous pushes.
    doingPush: false,
};

export const VatId = ({
   checkoutExtensionData,
   extensions,
   cart,
   addressType,
    isEditor = false
}) => {
    const gzdData = extensions.hasOwnProperty( 'woocommerce-germanized-pro' ) ? extensions['woocommerce-germanized-pro'] : {};
    const defaultVatId = gzdData.hasOwnProperty( `${ addressType }_vat_id` ) ? gzdData[ `${ addressType }_vat_id` ] : '';
    const mainAddress = 'shipping' === addressType ? cart.shippingAddress : cart.billingAddress;
    const country = mainAddress.country;
    const postcode = mainAddress.postcode;

    const [ vatId, setVatId ] = useState( defaultVatId );
    const [ disabled, setDisabled ] = useState(false );
    const [ currentCountry, setCurrentCountry ] = useState( country );
    const { setExtensionData, extensionData } = checkoutExtensionData;
    const { setValidationErrors, clearValidationError } = useDispatch( VALIDATION_STORE_KEY );
    const { __internalIncrementCalculating, __internalDecrementCalculating } = useDispatch( CHECKOUT_STORE_KEY );
    const validationErrorId = `${ addressType }_vat_id`;
    const show = isEditor ? true : showVatIdField( country, postcode, addressType );
    const isRelevantForVatExempt = cart.cartNeedsShipping ? 'shipping' === addressType : 'billing' === addressType;
    const additionalFieldsToValidate = getSetting( 'vatIdFieldsToValidate', [] );

    const { useShippingAsBilling } = useSelect(
        ( select ) => {
            return {
                useShippingAsBilling: select( CHECKOUT_STORE_KEY ).getUseShippingAsBilling()
            }
        }
    );

    useEffect( () => {
        if ( ! useShippingAsBilling ) {
            if ( 'billing' === addressType ) {
                const gzdCheckoutData = extensionData.hasOwnProperty( 'woocommerce-germanized-pro' ) ? extensionData['woocommerce-germanized-pro'] : {};
                const shippingVatId = gzdCheckoutData.hasOwnProperty( 'shipping_vat_id' ) ? gzdCheckoutData['shipping_vat_id'] : '';

                if ( shippingVatId ) {
                    onChangeVatId( shippingVatId );
                }
            }
        } else {
            /**
             * Need to directly set the data here as this effect won't be called
             * within the billing vat id block as the block does not show when shipping is
             * used as billing address.
             */
            setExtensionData( 'woocommerce-germanized-pro', 'billing_vat_id', '' );

            if ( ! cart.cartNeedsShipping ) {
                extensionCartUpdate( {
                    namespace: 'woocommerce-germanized-pro-vat-id',
                    data: {
                        'vat_id': '',
                        'address_type': 'billing'
                    }
                } );
            }
        }
    }, [
        useShippingAsBilling,
        addressType
    ] );

    const error = useSelect( ( select ) => {
        return select( VALIDATION_STORE_KEY ).getValidationError(
            validationErrorId
        );
    } );

    const { isCustomerDataUpdating } = useSelect(
        ( select ) => {
            return {
                isCustomerDataUpdating: select( CART_STORE_KEY ).isCustomerDataUpdating(),
            };
        }
    );

    // Register on change + load
    useEffect( () => {
        setExtensionData( 'woocommerce-germanized-pro', `${ addressType }_vat_id`, vatId );
    }, [
        vatId
    ] );

    const debouncedUpdateVatId = debounce( ( vatId ) => {
        if ( localState.doingPush ) {
            return;
        }

        // Make sure we are working on latest address data
        const customerData = select( CART_STORE_KEY ).getCustomerData();
        const mainAddress = 'shipping' === addressType ? customerData.shippingAddress : customerData.billingAddress;

        updateVatId( vatId, mainAddress );
    }, 4500 );

    useEffect( () => {
        if ( ! show || ( mainAddress.country !== currentCountry && ! isCustomerDataUpdating ) ) {
            setCurrentCountry( mainAddress.country );
            setVatId( '' );
        } else if ( vatId ) {
            inputRef.current?.revalidate();

            /**
             * Revalidate VAT ID as soon as address data changes to show
             * address validation errors.
             */
            if ( additionalFieldsToValidate.length > 0 ) {
                debouncedUpdateVatId.clear();

                clearValidationError( validationErrorId );
                debouncedUpdateVatId( vatId );
            }
        }
    }, [
        mainAddress.postcode,
        mainAddress.city,
        mainAddress.company,
        mainAddress.country,
        setVatId,
        show,
        setCurrentCountry
    ] );

    const debouncedSetExempt = debounce( ( vatId, addressType ) => {
        extensionCartUpdate( {
            namespace: 'woocommerce-germanized-pro-vat-id',
            data: {
                'vat_id': vatId,
                'address_type': addressType
            }
        } );
    }, 600 );

    const updateVatId = ( vatId, mainAddress ) => {
        if ( localState.doingPush ) {
            return;
        }

        // Prevent multiple pushes from happening at the same time.
        localState.doingPush = true;
        setDisabled( true );
        __internalIncrementCalculating();

        const fetchData = {
            vat_id: vatId,
            postcode: mainAddress.postcode,
            city: mainAddress.city,
            country: mainAddress.country,
            company: mainAddress.company,
            address_type: addressType,
        };

        triggerFetch( {
            path: '/wc/store/v1/cart/update-vat-id',
            method: 'POST',
            data: fetchData,
            cache: 'no-store',
            parse: false,
        } )
            .then( ( fetchResponse ) => {
                // Update nonce.
                triggerFetch.setNonce( fetchResponse.headers );

                // Handle response.
                fetchResponse.json().then( function ( response ) {
                    if ( ! fetchResponse.ok ) {
                        // We received an error response.
                        if ( response.body && response.body.message ) {
                            setValidationErrors( {
                                [ validationErrorId ]: {
                                    message: response.body.message,
                                    hidden: false,
                                },
                            } );
                        } else {
                            setValidationErrors( {
                                [ validationErrorId ]: {
                                    message: __( 'An error occurred while validating your vat id. Please try again.', 'woocommerce-germanized-pro' ),
                                    hidden: false,
                                },
                            } );
                        }
                    } else {
                        clearValidationError( validationErrorId );
                    }

                    localState.doingPush = false;
                    setDisabled( false );
                    __internalDecrementCalculating();

                    if ( isRelevantForVatExempt ) {
                        debouncedSetExempt( vatId, addressType );
                    }
                } );
            } )
            .catch( ( error ) => {
                error.json().then( function ( response ) {
                    setValidationErrors( {
                        [ validationErrorId ]: {
                            message: response.message,
                            hidden: false,
                        },
                    } );
                } );

                localState.doingPush = false;
                setDisabled( false );
                __internalDecrementCalculating();

                if ( isRelevantForVatExempt ) {
                    debouncedSetExempt( vatId, addressType );
                }
            } );
    };

    const onChangeVatId = useCallback(
        ( vatId ) => {
            setVatId( vatId );

            /**
             * Before validating via API: Check for any soft JS errors
             */
            const validationError = select( VALIDATION_STORE_KEY ).getValidationError(
                validationErrorId
            );

            if ( ! validationError && ! isCustomerDataUpdating ) {
                updateVatId( vatId, mainAddress );
            }
        },
        [
            setVatId,
            validationErrorId,
            isCustomerDataUpdating,
            setDisabled,
            isRelevantForVatExempt,
            mainAddress,
            setValidationErrors,
            clearValidationError
        ]
    );

    const fieldProps = {
        id: `${ addressType }-vat-id`,
        errorId: validationErrorId,
        label: __( 'VAT ID', 'woocommerce-germanized-pro' ),
        errorMessage: "",
        required: vatIdIsRequired( mainAddress.country, mainAddress.postcode ),
        className: `wc-block-components-address-form__${ addressType }_vat_id`,
        disabled: disabled
    };

    const inputRef = useRef( null );

    if ( ! show ) {
        return null;
    }

    return (
        <>
            <ValidatedTextInput
                ref={ inputRef }
                key={ fieldProps.id }
                value={ vatId }
                { ... fieldProps }
                onChange={ onChangeVatId }
                customFormatter={ ( value ) => {
                    value = value.toUpperCase();
                    value = value.replace( /[^\w\.]/g, '' );

                    return value;
                } }
                customValidation={ ( inputObject ) => {
                    if ( ! inputObject.value ) {
                        return true;
                    }

                    const value = inputObject.value.replace( /[^\w]/g, '' ); // remove dots before validating
                    const regex = /^(CHE[0-9]{9}|ATU[0-9]{8}|BE[01][0-9]{9}|BG[0-9]{9,10}|HR[0-9]{11}|CY[A-Z0-9]{9}|CZ[0-9]{8,10}|DK[0-9]{8}|EE[0-9]{9}|FI[0-9]{8}|FR[0-9A-Z]{2}[0-9]{9}|DE[0-9]{9}|EL[0-9]{9}|HU[0-9]{8}|IE([0-9]{7}[A-Z]{1,2}|[0-9][A-Z][0-9]{5}[A-Z])|IT[0-9]{11}|LV[0-9]{11}|LT([0-9]{9}|[0-9]{12})|LU[0-9]{8}|MT[0-9]{8}|NL[0-9]{9}B[0-9]{2}|PL[0-9]{10}|PT[0-9]{9}|RO[0-9]{2,10}|SK[0-9]{10}|SI[0-9]{8}|ES(([A-Z])([0-9]{7})([A-Z]|[0-9])|([0-9])([0-9]{7})([A-Z]))|SE[0-9]{12}|GB([0-9]{9}|[0-9]{12}|GD[0-4][0-9]{2}|HA[5-9][0-9]{2}))$/;

                    if ( ! regex.test( value ) ) {
                        inputObject.setCustomValidity(
                            __(
                                'The VAT ID you\'ve provided is not correct.',
                                'woocommerce-germanized-pro'
                            )
                        );

                        return false;
                    }

                    return true;
                } }
            />
            { disabled && <Spinner /> }
        </>
    );
};
