import { lazy } from "@wordpress/element";
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';

import metadata from './component-metadata';

registerCheckoutBlock({
    metadata: metadata.MULTILEVEL_CHECKOUT,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "multilevel-checkout-blocks/multilevel-checkout" */ './frontend'
                )
    ),
});

registerCheckoutBlock({
    metadata: metadata.MULTILEVEL_CHECKOUT_FIELDS,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "multilevel-checkout-blocks/multilevel-checkout-fields" */ './fields-block/frontend'
                )
    ),
});

registerCheckoutBlock({
    metadata: metadata.MULTILEVEL_CHECKOUT_SIDEBAR,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "multilevel-checkout-blocks/multilevel-checkout-sidebar" */ './sidebar/frontend'
                )
    ),
});

registerCheckoutBlock({
    metadata: metadata.MULTILEVEL_CHECKOUT_BILLING_ADDRESS,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "multilevel-checkout-blocks/multilevel-checkout-billing-address" */ './billing-address-block/frontend'
                )
    ),
});

registerCheckoutBlock({
    metadata: metadata.MULTILEVEL_CHECKOUT_SUBMIT,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "multilevel-checkout-blocks/multilevel-checkout-submit" */ './checkout-submit/frontend'
                )
    ),
});

registerCheckoutBlock({
    metadata: metadata.MULTILEVEL_CHECKOUT_STEP_FOOTER,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "multilevel-checkout-blocks/multilevel-checkout-step-footer" */ './step-footer/frontend'
                )
    ),
});

registerCheckoutBlock({
    metadata: metadata.MULTILEVEL_CHECKOUT_STEP_SUMMARY,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "multilevel-checkout-blocks/multilevel-checkout-step-summary" */ './step-summary/frontend'
                )
    ),
});

registerCheckoutBlock({
    metadata: metadata.MULTILEVEL_CHECKOUT_CONTACT_STEP,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "multilevel-checkout-blocks/multilevel-checkout-contact-step" */ './steps/step-contact/frontend'
                )
    ),
});

registerCheckoutBlock({
    metadata: metadata.MULTILEVEL_CHECKOUT_SHIPPING_STEP,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "multilevel-checkout-blocks/multilevel-checkout-shipping-step" */ './steps/step-shipping/frontend'
                )
    ),
});

registerCheckoutBlock({
    metadata: metadata.MULTILEVEL_CHECKOUT_PAYMENT_STEP,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "multilevel-checkout-blocks/multilevel-checkout-payment-step" */ './steps/step-payment/frontend'
                )
    ),
});

registerCheckoutBlock({
    metadata: metadata.MULTILEVEL_CHECKOUT_CONFIRMATION_STEP,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "multilevel-checkout-blocks/multilevel-checkout-confirmation-step" */ './steps/step-confirmation/frontend'
                )
    ),
});