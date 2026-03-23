/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';

import '../editor.scss';
import { VatId } from '../vat-id/util';

export const Edit = ({ attributes, setAttributes }) => {
	const cart = {
		shippingAddress: {
			country: 'DE',
			postcode: '12207'
		},
		billingAddress: {
			country: 'DE',
			postcode: '12207'
		}
	};

	const checkoutExtensionData = {
		setExtensionData: () => {},
		extensionData: {}
	};

	const extensions = {};
	const domRef = useRef( null );

	useEffect(() => {
		if ( domRef.current ) {
			const { ownerDocument } = domRef.current;
			const { defaultView } = ownerDocument;

			const blockElement = defaultView.document.getElementsByClassName( 'wp-block-woocommerce-checkout-billing-address-block' )[0];

			if ( undefined !== blockElement ) {
				const vatId = blockElement.getElementsByClassName( 'wc-gzd-billing-vat-id' )[0];

				if ( vatId ) {
					vatId.style.display = 'none';
				}

				const observer = new MutationObserver(() => {
					let addressFormWrapper = blockElement.getElementsByClassName( 'wc-block-components-address-form-wrapper' )[0];

					if ( ! addressFormWrapper ) {
						addressFormWrapper = blockElement.getElementsByClassName( 'wc-block-components-address-form' )[0];
					}

					if ( ! addressFormWrapper ) {
						addressFormWrapper = blockElement.getElementsByClassName( 'wc-block-components-address-address-wrapper' )[0];
					}

					if ( addressFormWrapper ) {
						observer.disconnect();
						vatId.style.display = 'block';
						addressFormWrapper.appendChild( vatId );
					}
				});

				observer.observe( blockElement, { subtree: true, childList: true } );
			}
		}
	}, [] );

	return (
		<div className="wc-gzd-vat-id wc-gzd-billing-vat-id" ref={ domRef }>
			<VatId
				cart={ cart }
				extensions={ extensions }
				checkoutExtensionData={ checkoutExtensionData }
				addressType={ 'billing' }
				isEditor={ true }
			/>
		</div>
	);
};

export const Save = ({ attributes }) => {
	return (
		<div {...useBlockProps.save()}>

		</div>
	);
};