import {
    Icon,
    institution as bank,
    currencyDollar as bill,
    payment as card,
} from '@wordpress/icons';
import { checkPayment } from './check-payment';
import { useCallback } from "@wordpress/element";
import classnames from "classnames";
import { isString, objectHasProp } from '@germanizedpro/types';

const namedIcons = {
    bank,
    bill,
    card,
    checkPayment
};

export const PaymentMethodLabel = ( {
 icon,
 text,
} ) => {
    const hasIcon = !! icon;
    const hasNamedIcon = useCallback(( iconToCheck ) =>
            hasIcon &&
            isString( iconToCheck ) &&
            objectHasProp( namedIcons, iconToCheck ),
        [ hasIcon ]
    );

    const className = classnames( 'wc-block-components-payment-method-label', {
        'wc-block-components-payment-method-label--with-icon': hasIcon,
    } );

    return (
        <span className={ className }>
            { hasNamedIcon( icon ) ? (
                <Icon icon={ namedIcons[ icon ] } />
            ) : (
                icon
            ) }
            { text }
        </span>
    );
};