import { useMultilevelCheckoutDataContext } from "../data"
import { useEffect, useState, useRef } from "@wordpress/element";
import { _x, sprintf } from "@wordpress/i18n";
import { CSSTransition } from 'react-transition-group';

export const Step = ({
    children,
    checkoutExtensionData,
    extensions,
    cart,
    stepName,
}) => {
    const [ active, setActive ] = useState( false );
    const nodeRef = useRef( null );

    const {
        currentStep,
        hasCompletedStep
    } = useMultilevelCheckoutDataContext();

    useEffect(() => {
        if ( currentStep === stepName ) {
            setActive( true );
        } else {
            setActive( false );
        }
    }, [ currentStep, setActive ] );

    let paymentNeedsShow = false;

    /**
     * Do not unmount the payment component to prevent losing certain data, e.g.
     * stored by third-party gateways after payment step has been completed.
     */
    if ( 'payment' === stepName && hasCompletedStep( 'payment' ) ) {
        paymentNeedsShow = true;
    }

	return (
        <CSSTransition
            in={ active }
            nodeRef={ nodeRef }
            timeout={ 300 }
            classNames="wc-gzdp-multilevel-checkout-step"
            appear
            unmountOnExit={ ! paymentNeedsShow }
        >
            <div
                className={"wp-block-woocommerce-germanized-pro-multilevel-checkout-step wp-block-woocommerce-germanized-pro-multilevel-checkout-step-" + stepName + " wc-gzdp-multilevel-checkout-step " + ( active ? 'step-active' : 'step-inactive' ) }
                ref={ nodeRef }
                data-step={ stepName }
            >
                { children }
            </div>
        </CSSTransition>
    );
};

export default Step;
