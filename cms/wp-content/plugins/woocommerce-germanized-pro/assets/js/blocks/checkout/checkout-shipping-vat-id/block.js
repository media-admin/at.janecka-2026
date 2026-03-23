/**
 * External dependencies
 */
import { VatId } from '../vat-id/util';
import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { CART_STORE_KEY } from '@woocommerce/block-data';

const Block = ({
   children,
   checkoutExtensionData,
   extensions,
   cart
}) => {
	const {
		cartDataLoaded,
	} = useSelect( ( select ) => {
		const store = select( CART_STORE_KEY );

		return {
			cartDataLoaded: store.hasFinishedResolution( 'getCartData' ),
		};
	} );

	useEffect(() => {
		if ( cartDataLoaded ) {
			const blockElement = document.getElementsByClassName( 'wp-block-woocommerce-checkout-shipping-address-block' )[0];
			const vatId = blockElement.getElementsByClassName( 'wc-gzd-shipping-vat-id' )[0];
			let addressFormWrapper = blockElement.getElementsByClassName( 'wc-block-components-address-form' )[0];

			if ( ! addressFormWrapper ) {
				addressFormWrapper = blockElement.getElementsByClassName( 'wc-block-components-address-form-wrapper' )[0];
			}

			if ( addressFormWrapper && vatId ) {
				addressFormWrapper.appendChild( vatId );
			}
		}
	}, [
		cartDataLoaded
	] );

	return (
		<>
		<div className="wc-gzd-vat-id-element-placeholder"></div>
		<div className="wc-gzd-vat-id wc-gzd-shipping-vat-id">
			<VatId
				cart={ cart }
				extensions={ extensions }
				checkoutExtensionData={ checkoutExtensionData }
				addressType={ 'shipping' }
			/>
		</div>
		</>
	);
};

export default Block;
