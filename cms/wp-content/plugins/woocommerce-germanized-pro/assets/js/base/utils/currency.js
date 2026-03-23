export const getCurrencyFromPriceResponse = (
    // Currency data object, for example an API response containing currency formatting data.
    currencyData
) => {
    if ( ! currencyData?.currency_code ) {
        return {};
    }

    const {
        currency_code: code,
        currency_symbol: symbol,
        currency_thousand_separator: thousandSeparator,
        currency_decimal_separator: decimalSeparator,
        currency_minor_unit: minorUnit,
        currency_prefix: prefix,
        currency_suffix: suffix,
    } = currencyData;

    return {
        code: code || 'USD',
        symbol: symbol || '$',
        thousandSeparator:
            typeof thousandSeparator === 'string' ? thousandSeparator : ',',
        decimalSeparator:
            typeof decimalSeparator === 'string' ? decimalSeparator : '.',
        minorUnit: Number.isFinite( minorUnit ) ? minorUnit : 2,
        prefix: typeof prefix === 'string' ? prefix : '$',
        suffix: typeof suffix === 'string' ? suffix : '',
    };
};