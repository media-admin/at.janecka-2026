import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { archive } from '@wordpress/icons';
import { getDefaultPlaceholderContent } from "@storeabill/utils";

import edit from './edit';
import save from './save';

const settings = {
    title: _x( 'Preferential Origin Declaration', 'storeabill-core', 'storeabill' ),
    description: _x( 'Inserts a preferential origin declaration in case goods from the EU are included.', 'storeabill-core', 'storeabill' ),
    category: 'storeabill',
    icon: archive,
    example: {},
    supports: {
        html: false,
        className: false
    },
    attributes: {
        "content": {
            "type": 'string',
            "source": 'html',
            "selector": 'p',
            "default": _x( 'The exporter of the products covered by this document declares that, except where otherwise clearly indicated, these products are of %s preferential origin.', 'storeabill-core', 'storeabill' ).replace( '%s', getDefaultPlaceholderContent( '{content}', _x( 'Origin', 'storeabill-core', 'storeabill' ) ) )
        },
        "align": {
            "type": 'string',
            "default": 'left'
        },
        "textColor": {
            "type": "string"
        },
        "customTextColor": {
            "type": "string"
        },
        "fontSize": {
            "type": "string"
        },
        "customFontSize": {
            "type": "string"
        },
    },
    edit,
    save,
};

registerBlockType( 'storeabill/preferential-origin-declaration', settings );