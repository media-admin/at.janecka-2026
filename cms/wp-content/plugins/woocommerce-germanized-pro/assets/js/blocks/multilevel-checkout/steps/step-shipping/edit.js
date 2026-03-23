/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';

import '../editor.scss';

export const Edit = ({ attributes, setAttributes }) => {
	return (
		<div className="wc-gzdp-multilevel-checkout">

		</div>
	);
};

export const Save = ({ attributes }) => {
	return (
		<div {...useBlockProps.save()}>

		</div>
	);
};
