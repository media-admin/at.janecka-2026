import {
    createContext,
    useContext,
    useReducer,
    useEffect,
    useMemo,
    useRef,
    useCallback
} from '@wordpress/element';

import { CART_STORE_KEY, CHECKOUT_STORE_KEY, VALIDATION_STORE_KEY, PAYMENT_STORE_KEY } from '@woocommerce/block-data';
import { useDispatch, useSelect, select, dispatch } from '@wordpress/data';
import { _x, sprintf } from "@wordpress/i18n";
import { useStoreEvents } from "./store-events";
import { scrollToHTMLElement } from "./scroll-to";
import { getSetting } from '@germanizedpro/settings';
import classnames from "classnames";

const storePages = getSetting( 'storePages', {} );

let cartUrl = '';

if ( storePages.hasOwnProperty( 'cart' ) ) {
    cartUrl = storePages.cart.permalink;
}

const removeAllNotices = () => {
    const containers = select(
        'wc/store/store-notices'
    ).getRegisteredContainers();
    const { removeNotice } = dispatch( 'core/notices' );
    const { getNotices } = select( 'core/notices' );

    containers.forEach( ( container ) => {
        getNotices( container ).forEach( ( notice ) => {
            removeNotice( notice.id, container );
        } );
    } );
};

const steps = {
    'contact': {
        'id': 'contact',
        'title': _x( 'Contact', 'multilevel-checkout-step', 'woocommerce-germanized-pro' ),
        'prevTitle': _x( 'Back to Contact', 'multilevel-checkout-step', 'woocommerce-germanized-pro' ),
        'nextTitle': _x( 'Continue with Contact', 'multilevel-checkout-step', 'woocommerce-germanized-pro' ),
    },
    'shipping': {
        'id': 'shipping',
        'title': _x( 'Shipping', 'multilevel-checkout-step', 'woocommerce-germanized-pro' ),
        'prevTitle': _x( 'Back to Shipping', 'multilevel-checkout-step', 'woocommerce-germanized-pro' ),
        'nextTitle': _x( 'Continue with Shipping', 'multilevel-checkout-step', 'woocommerce-germanized-pro' ),
    },
    'payment': {
        'id': 'payment',
        'title': _x( 'Payment', 'multilevel-checkout-step', 'woocommerce-germanized-pro' ),
        'prevTitle': _x( 'Back to Payment', 'multilevel-checkout-step', 'woocommerce-germanized-pro' ),
        'nextTitle': _x( 'Continue with Payment', 'multilevel-checkout-step', 'woocommerce-germanized-pro' ),
    },
    'confirmation': {
        'id': 'confirmation',
        'title': _x( 'Confirmation', 'multilevel-checkout-step', 'woocommerce-germanized-pro' ),
        'prevTitle': _x( 'Back to Confirmation', 'multilevel-checkout-step', 'woocommerce-germanized-pro' ),
        'nextTitle': _x( 'Continue with Confirmation', 'multilevel-checkout-step', 'woocommerce-germanized-pro' ),
    }
};

const stepsOrder = [
    'contact',
    'shipping',
    'payment',
    'confirmation'
];

const defaultCheckoutData = {
    currentStep: 'contact',
    cartUrl: cartUrl,
    showCartLink: getSetting( 'addCartLink', true ),
    availableSteps: stepsOrder,
    stepsCompleted: [],
    stepsViewed: [],
    isProcessing: false,
    getPrevStep: () => null,
    getNextStep: () => null,
    getPrevStepTitle: () => null,
    getNextStepTitle: () => null,
    onNextStep: () => null,
    onPrevStep: () => null,
    onChangeStep: ( stepId ) => null,
    getStepTitle: ( stepId ) => null,
    getStepById: ( stepId ) => null,
    hasCompletedStep: ( stepId ) => null,
    hasViewedStep: ( stepId ) => null,
};

const MultilevelCheckoutDataContext = createContext( defaultCheckoutData );

export const ACTION_TYPES = {
    SET_CURRENT_STEP: 'set_current_step',
    SET_NEXT_STEP: 'set_next_step',
    SET_AVAILABLE_STEPS: 'set_available_steps',
    SET_PROCESSING: 'set_processing',
};

export const useMultilevelCheckoutDataContext = () => {
    return useContext( MultilevelCheckoutDataContext );
};

const actions = {
    setCurrentStep: ( currentStep ) => ( {
        type: ACTION_TYPES.SET_CURRENT_STEP,
        currentStep
    } ),
    setNextStep: ( nextStep ) => ( {
        type: ACTION_TYPES.SET_NEXT_STEP,
        nextStep
    } ),
    setAvailableSteps: ( availableSteps ) => ( {
        type: ACTION_TYPES.SET_AVAILABLE_STEPS,
        availableSteps
    } ),
    setProcessing: ( processing ) => ( {
        type: ACTION_TYPES.SET_PROCESSING,
        processing
    } )
};

const reducer = (state, { currentStep, nextStep, availableSteps, processing, type } ) => {
    let newState;

    switch ( type ) {
        case ACTION_TYPES.SET_CURRENT_STEP:
            newState =
                currentStep !== state.currentStep
                    ? {
                        ...state,
                        currentStep,
                        stepsViewed: state.stepsViewed.includes( currentStep ) ? state.stepsViewed : state.stepsViewed.concat( [currentStep] )
                    }
                    : state;
            break;
        case ACTION_TYPES.SET_NEXT_STEP:
            newState =
                nextStep !== state.currentStep
                    ? {
                        ...state,
                        currentStep: nextStep,
                        stepsCompleted: state.stepsCompleted.includes( state.currentStep ) ? state.stepsCompleted : state.stepsCompleted.concat( [state.currentStep] ),
                        stepsViewed: state.stepsViewed.includes( nextStep ) ? state.stepsViewed : state.stepsViewed.concat( [nextStep] )
                    }
                    : state;
            break;
        case ACTION_TYPES.SET_AVAILABLE_STEPS:
            newState =
                availableSteps !== state.availableSteps
                    ? {
                        ...state,
                        availableSteps,
                    }
                    : state;
            break;
        case ACTION_TYPES.SET_PROCESSING:
            newState =
                processing !== state.isProcessing
                    ? {
                        ...state,
                        isProcessing: processing,
                    }
                    : state;
            break;
    }

    return newState;
};

const noticeContexts = {
    CART: 'wc/cart',
    CHECKOUT: 'wc/checkout',
    PAYMENTS: 'wc/checkout/payments',
    EXPRESS_PAYMENTS: 'wc/checkout/express-payments',
    CONTACT_INFORMATION: 'wc/checkout/contact-information',
    SHIPPING_ADDRESS: 'wc/checkout/shipping-address',
    BILLING_ADDRESS: 'wc/checkout/billing-address',
    SHIPPING_METHODS: 'wc/checkout/shipping-methods',
    CHECKOUT_ACTIONS: 'wc/checkout/checkout-actions',
}

/**
 * The shipping data provider exposes the interface for shipping in the checkout/cart.
 */
export const MultilevelCheckoutDataProvider = ( {
  children,
} ) => {
    const [ state, dispatch ] = useReducer( reducer, defaultCheckoutData );
    const domNodeRef = useRef( null );
    const { showAllValidationErrors } = useDispatch( VALIDATION_STORE_KEY );
    const { __internalSetHasError } = useDispatch( CHECKOUT_STORE_KEY );
    const {
        __internalSetBeforeProcessing,
        __internalSetIdle,
        __internalSetUseShippingAsBilling
    } = useDispatch( CHECKOUT_STORE_KEY );

    const dispatchActions = useMemo(
        () => ( {
            setCurrentStep: ( currentStep ) => dispatch( actions.setCurrentStep( currentStep ) ),
            setNextStep: ( nextStep ) => dispatch( actions.setNextStep( nextStep ) ),
            setAvailableSteps: ( availableSteps ) => dispatch( actions.setAvailableSteps( availableSteps ) ),
            setProcessing: ( processing ) => dispatch( actions.setProcessing( processing ) ),
        } ),
        []
    );

    const {
        needsShipping,
    } = useSelect( ( select ) => {
        const store = select( CART_STORE_KEY );

        return {
            needsShipping: store.getNeedsShipping(),
        }
    } );

    const {
        checkoutIsProcessing,
        checkoutStatus,
        isCheckoutBeforeProcessing,
        isCheckoutAfterProcessing,
        checkoutHasError
    } = useSelect( ( select ) => {
        const store = select( CHECKOUT_STORE_KEY );

        return {
            checkoutIsProcessing: store.isProcessing(),
            checkoutStatus: store.getCheckoutStatus(),
            isCheckoutBeforeProcessing: store.isBeforeProcessing(),
            isCheckoutAfterProcessing: store.isAfterProcessing(),
            checkoutHasError: store.hasError()
        }
    } );

    useEffect(() => {
        if ( checkoutIsProcessing ) {
            dispatchActions.setProcessing( true );
        } else {
            dispatchActions.setProcessing( false );
        }
    }, [ checkoutIsProcessing, dispatchActions.setProcessing ] );

    useEffect(() => {
        let availableSteps = defaultCheckoutData.availableSteps;
        const stepsExcluded = [];

        if ( ! needsShipping ) {
            stepsExcluded.push( 'shipping' );
        }

        if ( stepsExcluded.length > 0 ) {
            availableSteps = availableSteps.filter( ( stepName ) => {
                if ( stepsExcluded.includes( stepName ) ) {
                    return false;
                }

                return true;
            } );

            dispatchActions.setAvailableSteps( availableSteps );
        }
    }, [ needsShipping, dispatchActions.setAvailableSteps ] );

    useEffect(() => {
        if ( ! state.stepsViewed.includes( state.currentStep ) ) {
            state.stepsViewed = state.stepsViewed.concat( [state.currentStep] );
        }
    }, [ state.currentStep, state.stepsViewed ] );

    useEffect(() => {
        __internalSetUseShippingAsBilling( true );
    }, [] );

    const hasValidationErrors = useSelect(
        ( select ) => select( VALIDATION_STORE_KEY ).hasValidationErrors
    );

    const checkValidation = useCallback( () => {
        const { hasError } = select( CHECKOUT_STORE_KEY );
        const { getNotices } = select( 'core/notices' );
        const checkoutContexts = Object.values( noticeContexts ).filter(
            ( context ) =>
                context !== noticeContexts.PAYMENTS &&
                context !== noticeContexts.EXPRESS_PAYMENTS
        );
        const allCheckoutNotices = checkoutContexts.reduce(
            ( acc, context ) => {
                return [ ...acc, ...getNotices( context ) ];
            },
            []
        );

        const paymentNotices = getNotices( noticeContexts.PAYMENTS );
        let stepHasError = hasValidationErrors() || hasError();

        if ( 'payment' === state.currentStep ) {
            /**
             * The unknown_error is expected to be thrown as the fetch/XHR method is overridden.
             */
            if ( stepHasError ) {
                let relevantErrors = allCheckoutNotices.filter(
                    ( notice ) => {
                        return notice['status'] === 'error';
                    }
                );

                if ( relevantErrors.length === 1 && 'unknown_error' === relevantErrors[0]['id'] ) {
                    stepHasError = false;
                }
            }

            if ( paymentNotices.length > 0 ) {
                stepHasError = true;
            }
        }

        if ( stepHasError ) {
            return false;
        }

        return true;
    }, [ hasValidationErrors, state.currentStep ] );

    const getNextStepById = useCallback( ( stepId ) => {
        const index = state.availableSteps.indexOf( stepId );

        if ( state.availableSteps.length >= index + 1 ) {
            return steps[ state.availableSteps[ index + 1 ] ];
        }

        return undefined;
    }, [ state.availableSteps ] );

    const getPrevStepById = useCallback( ( stepId ) => {
        const index = state.availableSteps.indexOf( stepId );

        if ( index - 1 >= 0 ) {
            return steps[ state.availableSteps[ index - 1 ] ];
        }

        return undefined;
    }, [ state.availableSteps ] );

    const getStepById = useCallback( ( stepId ) => {
        return steps.hasOwnProperty( stepId ) ? steps[ stepId ] : undefined;
    }, [] );

    const getStepTitle = useCallback( ( stepId ) => {
        const step = getStepById( stepId );

        return step ? step.title : '';
    } , [ getStepById ] );

    const getNextStep = useCallback( () => {
        return getNextStepById( state.currentStep );
    }, [ state.currentStep, getNextStepById ] );

    const getPrevStep = useCallback( () => {
        return getPrevStepById( state.currentStep );
    }, [ state.currentStep, getPrevStepById ] );

    const getNextStepTitle = useCallback( () => {
        const next = getNextStep( state.currentStep );
        return next ? next.nextTitle : '';
    }, [ state.currentStep, getNextStep, getStepTitle ] );

    const getPrevStepTitle = useCallback( () => {
        const prev = getPrevStep( state.currentStep );
        return prev ? prev.prevTitle : '';
    }, [ state.currentStep, getPrevStep, getStepTitle ] );

    const hasCompletedStep = useCallback( ( stepName ) => {
        return state.stepsCompleted.includes( stepName );
    }, [ state.stepsCompleted ] );

    const hasViewedStep = useCallback( ( stepName ) => {
        return state.stepsViewed.includes( stepName );
    }, [ state.stepsViewed ] );

    const maybeSwitchStep = useCallback( ( stepId, forceValidation = false ) => {
        const { ownerDocument } = domNodeRef.current;
        const { defaultView } = ownerDocument;

        const checkout = defaultView?.document.getElementsByClassName( 'wc-block-checkout' );

        if ( checkout.length ) {
            checkout[0].classList.add( 'is-switching-step' );
        }

        dispatchActions.setProcessing( true );

        let scrollToTopTimeout;

        removeAllNotices();
        __internalSetHasError( false );
        const originalFetch = window.fetch;

        if ( 'payment' === state.currentStep ) {
            window.fetch = function( url, init ) {
                if ( url.includes( 'wc/store/v1/checkout' ) ) {
                    return false;
                }

                return originalFetch( url, init );
            };

            __internalSetBeforeProcessing();
        }

        /**
         * Allow switching confirmation step without validation.
         */
        if ( ! forceValidation && 'confirmation' === state.currentStep ) {
            dispatchActions.setProcessing( true );

            removeAllNotices();
            dispatchActions.setCurrentStep( stepId );

            if ( domNodeRef.current !== null ) {
                scrollToTopTimeout = window.setTimeout( () => {
                    scrollToHTMLElement( domNodeRef );
                }, 50 );
            }

            dispatchActions.setProcessing( false );

            if ( checkout.length ) {
                checkout[0].classList.remove( 'is-switching-step' );
            }
        } else {
            window.setTimeout( () => {
                window.fetch = originalFetch;

                if ( checkValidation() ) {
                    removeAllNotices();
                    __internalSetHasError( false );

                    dispatchActions.setNextStep( stepId );

                    if ( domNodeRef.current !== null ) {
                        scrollToTopTimeout = window.setTimeout( () => {
                            scrollToHTMLElement( domNodeRef );
                        }, 50 );
                    }
                } else {
                    __internalSetHasError( true );
                    showAllValidationErrors();

                    if ( domNodeRef.current !== null ) {
                        // Scroll after a short timeout to allow a re-render. This will allow focusableSelector to match updated components.
                        scrollToTopTimeout = window.setTimeout( () => {
                            scrollToHTMLElement( domNodeRef, {
                                focusableSelector: 'input:invalid, .has-error input',
                            } );
                        }, 50 );
                    }
                }

                dispatchActions.setProcessing( false );

                if ( checkout.length ) {
                    checkout[0].classList.remove( 'is-switching-step' );
                }
            }, 75 );
        }

        return () => {
            clearTimeout( scrollToTopTimeout );
            dispatchActions.setProcessing( false );

            if ( checkout.length ) {
                checkout[0].classList.remove( 'is-switching-step' );
            }
        };
    }, [
        dispatchActions.setNextStep,
        dispatchActions.setProcessing,
        domNodeRef,
        showAllValidationErrors,
        state.currentStep,
        state.isProcessing,
        checkValidation,
        __internalSetBeforeProcessing
    ] );

    const onNextStep = useCallback( () => {
        const nextStep = getNextStep( state.currentStep ).id;

        return maybeSwitchStep( nextStep, true );
    }, [
        state.currentStep,
        getNextStep,
        maybeSwitchStep
    ] );

    const onPrevStep = useCallback( () => {
        const prevStep = getPrevStep( state.currentStep ).id;

        return maybeSwitchStep( prevStep );
    }, [
        state.currentStep,
        getPrevStep,
        maybeSwitchStep
    ] );

    const onChangeStep = useCallback( ( stepId ) => {
        const step = getStepById( stepId );
        const hasViewed = hasViewedStep( stepId );

        if ( state.currentStep !== stepId && step && hasViewed ) {
            return maybeSwitchStep( stepId );
        }

        return {};
    }, [
        state.currentStep,
        getStepById,
        hasViewedStep,
        maybeSwitchStep
    ] );

    /**
     * Catch (payment) errors during confirmation step and automatically
     * navigate back to the payment step in case payment notices were found.
     */
    useEffect( () => {
        if ( 'confirmation' === state.currentStep ) {
            if ( checkoutHasError && ( 'processing' === checkoutStatus || 'after_processing' === checkoutStatus ) ) {
                const { getNotices } = select( 'core/notices' );
                const paymentNotices = getNotices( noticeContexts.PAYMENTS );

                if ( paymentNotices.length > 0 ) {
                    dispatchActions.setCurrentStep( 'payment' );

                    if ( domNodeRef.current !== null ) {
                        window.setTimeout( () => {
                            scrollToHTMLElement( domNodeRef, { focusableSelector: '.wc-block-store-notice' } );
                        }, 50 );
                    }
                }
            }
        }
    }, [
        checkoutHasError,
        isCheckoutAfterProcessing,
        checkoutStatus,
        domNodeRef,
        state.currentStep,
        dispatchActions.setCurrentStep,
        dispatchActions.setProcessing,
    ] );

    const MultilevelCheckoutData = {
        currentStep: state.currentStep || 'contact',
        stepsCompleted: state.stepsCompleted || [],
        cartUrl: state.cartUrl || '',
        showCartLink: state.showCartLink,
        stepsViewed: state.stepsViewed || [],
        availableSteps: state.availableSteps,
        isProcessing: state.isProcessing,
        getNextStep,
        getPrevStep,
        getNextStepTitle,
        getPrevStepTitle,
        getStepTitle,
        getStepById,
        onChangeStep,
        onNextStep,
        onPrevStep,
        hasCompletedStep,
        hasViewedStep,
        dispatchActions,
        needsShipping
    };

    return (
        <MultilevelCheckoutDataContext.Provider value={ MultilevelCheckoutData }>
            <div
                ref={ domNodeRef }
                className={ classnames(
                    'wp-block-woocommerce-germanized-pro-multilevel-checkout wc-gzdp-multilevel-checkout',
                    `step-${ MultilevelCheckoutData.currentStep }`,
                    {
                        'is-processing': MultilevelCheckoutData.isProcessing,
                    }
                ) }
            >
                { children }
            </div>
        </MultilevelCheckoutDataContext.Provider>
    );
};