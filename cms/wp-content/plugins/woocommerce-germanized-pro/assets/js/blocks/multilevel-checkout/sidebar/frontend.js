import classnames from "classnames";

export const Frontend = ({
  children,
  className,
}) => {
    return (
        <div
            className={ classnames({
                'wc-block-components-sidebar wc-block-checkout__sidebar': true,
                'wp-block-woocommerce-germanized-pro-multilevel-checkout-sidebar': true,
                'woocommerce-gzdp-multilevel-checkout-sidebar': true,
            } ) }
            >
            { children }
        </div>
    );
};

export default Frontend;
