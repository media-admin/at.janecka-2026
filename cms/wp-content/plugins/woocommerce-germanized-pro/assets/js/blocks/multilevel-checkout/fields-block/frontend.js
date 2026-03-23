import classnames from 'classnames';
import { useEffect } from '@wordpress/element';
import { forwardRef } from '@wordpress/element';
import { useStoreEvents } from "../store-events";
import { Breadcrumbs } from "../breadcrumbs";

const Main = forwardRef(
    ( { children, className = '' }, ref ) => {
        return (
            <div
                ref={ ref }
                className={ classnames(
                    'wc-block-components-main',
                    className
                ) }
            >
                { children }
            </div>
        );
    }
);

export const Frontend = ({
  children,
  className,
}) => {
    const { dispatchCheckoutEvent } = useStoreEvents();

    // Ignore changes to dispatchCheckoutEvent callback so this is ran on first mount only.
    useEffect( () => {
        dispatchCheckoutEvent( 'render-checkout-form' );
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [] );

    // Do not re-render forced-blocks
    const newChildren = children[0];

    return (
        <Main className={ classnames( 'wc-block-checkout__main', className ) }>
            <Breadcrumbs />
            <form className="wc-block-components-form wc-block-checkout__form">
                { newChildren }
            </form>
        </Main>
    );
};

export default Frontend;
