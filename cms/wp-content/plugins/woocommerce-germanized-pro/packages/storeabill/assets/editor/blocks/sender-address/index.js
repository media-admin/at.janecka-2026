import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { getDefaultPlaceholderContent } from "@storeabill/utils";
import { address } from '@storeabill/icons';

import edit from './edit';
import save from './save';

const settings = {
    title: _x( 'Sender Address', 'storeabill-core', 'storeabill' ),
    description: _x( 'Inserts the sender address.', 'storeabill-core', 'storeabill' ),
    category: 'storeabill',
    icon: address,
    supports: {
        html: false,
    },
    example: {},
    attributes: {
        "align": {
            "type": "string",
            "default": "left",
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
        "content": {
            "type": 'string',
            "source": 'html',
            "selector": 'p.address-content',
            "default": getDefaultPlaceholderContent( '{content}' )
        },
        "heading": {
            "type": 'string',
            "source": 'html',
            "selector": 'p.address-heading',
            "default": ''
        },
    },
    edit,
    save
};

registerBlockType( 'storeabill/sender-address', settings );