import classnames from 'classnames';
import { useMultilevelCheckoutDataContext } from "../data";
import { _x, sprintf } from "@wordpress/i18n";
import {
    createContext,
    useContext,
    useReducer,
    useEffect,
    useMemo,
    useRef,
    useCallback
} from '@wordpress/element';
import {
    Icon,
    chevronLeft,
} from '@wordpress/icons';
import {
    Spinner,
} from "@germanizedpro/base-components/spinner";

export const Footer = () => {
    const {
        currentStep,
        showCartLink,
        cartUrl,
        isProcessing,
        onPrevStep,
        getPrevStepTitle,
        onNextStep,
        getPrevStep,
        getNextStep,
        getNextStepTitle
    } = useMultilevelCheckoutDataContext();

    if ( 'confirmation' === currentStep ) {
        return null;
    }

    return (
        <fieldset className="wc-block-components-checkout-step wc-gzdp-multilevel-checkout-step-footer">
            { getPrevStep() ?
                <a
                    href="#"
                    className="prev-step"
                    onClick={ onPrevStep }
                    key="prev-step"
                    aria-disabled={ isProcessing }
                >
                    <Icon icon={ chevronLeft } />
                    { getPrevStepTitle() }
                </a>
                : showCartLink ?
                <a
                    href={ cartUrl }
                    className="prev-step"
                    key="back-to-cart"
                >
                    <Icon icon={ chevronLeft }/>
                    { _x( 'Back to cart', 'multilevel-checkout', 'woocommerce-germanized-pro') }
                </a> : ''
            }
            { getNextStep() &&
                <button
                    type="button"
                    className="components-button wc-block-components-button wp-element-button wc-block-components-checkout-place-order-button contained"
                    onClick={ onNextStep }
                    disabled={ isProcessing }
                >
                    { isProcessing && <Spinner /> }
                    <span className="wc-block-components-button__text">{ getNextStepTitle() }</span>
                </button>
            }
        </fieldset>
    );
};

export default Footer;
