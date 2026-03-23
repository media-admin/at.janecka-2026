export const getSelectedShippingMethod = (
    shippingRates
) => {
    let currentRate = {};

    shippingRates.map( ( { package_id: packageId, shipping_rates: packageRates } ) => {
        const selected = packageRates.find( ( rate ) => rate.selected ) || false;

        if ( selected ) {
            currentRate = selected;
        }
    } );

    return currentRate;
};
export const rateHasPickup = (
    rate
) => {
    const pickupMethods = ['pickup_location'];

    if ( pickupMethods.includes( rate.method_id ) ) {
        return true;
    }

    return false;
};