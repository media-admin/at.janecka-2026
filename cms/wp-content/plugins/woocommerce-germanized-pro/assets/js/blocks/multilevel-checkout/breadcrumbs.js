import classnames from 'classnames';
import { useMultilevelCheckoutDataContext } from "./data";
import {
    Icon,
    chevronRight,
} from '@wordpress/icons';
import { _x } from "@wordpress/i18n";

export const Breadcrumbs = () => {
    const {
        currentStep,
        onChangeStep,
        availableSteps,
        showCartLink,
        cartUrl,
        hasCompletedStep,
        hasViewedStep,
        getStepTitle,
    } = useMultilevelCheckoutDataContext();

    let stepCount = 0;

    return (
        <nav className="wc-gzdp-multilevel-checkout-breadcrumbs">
            <ol>
                { showCartLink &&
                    <li
                        key={ 'cart' }
                        className={ classnames({
                            'active': false,
                            'completed': true,
                            'viewed': true
                        } ) }
                    >
                        <a className="breadcrumb-link" href={ cartUrl }>{ _x( 'Cart', 'multilevel-checkout-step', 'woocommerce-germanized-pro' ) }</a>
                        <Icon icon={ chevronRight } />
                    </li>
                }
                { availableSteps.map( ( stepId) => {
                    stepCount++;

                    const isActive = stepId === currentStep;
                    const hasViewed = hasViewedStep( stepId );
                    const isLastStep = availableSteps.length === stepCount;

                    return (
                        <li
                            key={ stepId }
                            className={ classnames({
                                'active': isActive,
                                'completed': hasCompletedStep( stepId ),
                                'viewed': hasViewed
                            } ) }
                        >
                            { ( isActive || ! hasViewed ) ? <span className="breadcrumb-text">{ getStepTitle( stepId ) }</span> : <a className="breadcrumb-link" href="#" onClick={ () => onChangeStep( stepId ) }>{ getStepTitle( stepId ) }</a> }
                            { ! isLastStep && <Icon icon={ chevronRight } /> }
                        </li>
                    )
                }) }
            </ol>
        </nav>
    );
};

export default Breadcrumbs;
