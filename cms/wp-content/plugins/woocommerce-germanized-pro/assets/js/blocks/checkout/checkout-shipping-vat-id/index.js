/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, mapMarker } from "@wordpress/icons";

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import './style.scss';
import metadata from './block.json';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ mapMarker }
				className="wc-block-editor-components-block-icon"
			/>
		)
	},
	edit: Edit,
	save: Save,
});
