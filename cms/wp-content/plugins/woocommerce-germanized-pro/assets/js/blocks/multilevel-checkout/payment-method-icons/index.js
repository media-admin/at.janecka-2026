/**
 * External dependencies
 */
import classnames from 'classnames';
import { isString, objectHasProp } from '@germanizedpro/types';

/**
 * Internal dependencies
 */
import PaymentMethodIcon from './payment-method-icon';
import { getCommonIconProps } from './common-icons';

const normalizeIconConfig = (
    icons
) => {
    const normalizedIcons= {};

    icons.forEach( ( raw ) => {
        let icon= {};

        if ( typeof raw === 'string' ) {
            icon = {
                id: raw,
                alt: raw,
                src: null,
            };
        }

        if ( typeof raw === 'object' ) {
            icon = {
                id: raw.id || '',
                alt: raw.alt || '',
                src: raw.src || null,
            };
        }

        if ( icon.id && isString( icon.id ) && ! normalizedIcons[ icon.id ] ) {
            normalizedIcons[ icon.id ] = icon;
        }
    } );

    return Object.values( normalizedIcons );
};

/**
 * For a given list of icons, render each as a list item, using common icons
 * where available.
 */
export const PaymentMethodIcons = ( {
     icons = [],
     align = 'center',
     className,
    } ) => {
    const iconConfigs = normalizeIconConfig( icons );

    if ( iconConfigs.length === 0 ) {
        return null;
    }

    const containerClass = classnames(
        'wc-block-components-payment-method-icons',
        {
            'wc-block-components-payment-method-icons--align-left':
                align === 'left',
            'wc-block-components-payment-method-icons--align-right':
                align === 'right',
        },
        className
    );

    return (
        <div className={ containerClass }>
            { iconConfigs.map( ( icon ) => {
                const iconProps = {
                    ...icon,
                    ...getCommonIconProps( icon.id ),
                };
                return (
                    <PaymentMethodIcon
                        key={ 'payment-method-icon-' + icon.id }
                        { ...iconProps }
                    />
                );
            } ) }
        </div>
    );
};