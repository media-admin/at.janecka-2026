/**
 * External dependencies
 */
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';

import metadata from './component-metadata';
import CheckoutBillingVatId from './checkout-billing-vat-id/frontend';
import CheckoutShippingVatId from './checkout-shipping-vat-id/frontend';

registerCheckoutBlock({
    metadata: metadata.CHECKOUT_BILLING_VAT_ID,
    component: CheckoutBillingVatId
});

registerCheckoutBlock({
    metadata: metadata.CHECKOUT_SHIPPING_VAT_ID,
    component: CheckoutShippingVatId
});