import { _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { meta } from '@storeabill/icons';

import saveV1 from './save-v1';
import edit from './edit';
import save from './save';

const settings = {
    title: _x( 'Item Meta', 'storeabill-core', 'storeabill' ),
    description: _x( 'Inserts item meta data.', 'storeabill-core', 'storeabill' ),
    category: 'storeabill',
    icon: meta,
    parent: [ 'storeabill/item-table-column' ],
    example: {},
    attributes: {
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
        "isDisabled": {
            "type": "boolean",
            "default": false,
        },
        "itemType": {
            "type": "string",
            "default": "",
        },
        "metaType": {
            "type": "string"
        },
        "hideIfEmpty": {
            "type": "boolean",
            "default": true,
        },
        "content": {
            "type": 'string',
            "source": 'html',
            "selector": 'div.sab-block-item-content',
            "default": '{content}'
        },
    },
    edit,
    save,
    deprecated: [
        {
            attributes: {
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
                "isDisabled": {
                    "type": "boolean",
                    "default": false,
                },
                "itemType": {
                    "type": "string",
                    "default": "",
                },
                "metaType": {
                    "type": "string"
                },
                "hideIfEmpty": {
                    "type": "boolean",
                    "default": true,
                },
                "content": {
                    "type": 'string',
                    "source": 'html',
                    "selector": 'p.sab-block-item-content',
                    "default": '{content}'
                },
            },
            save( attributes ) {
                return saveV1( attributes );
            }
        },
        {
            attributes: {
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
                    "type": "number"
                },
                "metaType": {
                    "type": "string"
                },
                "hideIfEmpty": {
                    "type": "boolean",
                    "default": true,
                },
                "content": {
                    "type": 'string',
                    "source": 'html',
                    "selector": 'p.sab-block-item-content',
                    "default": '{content}'
                },
            },
            isEligible( { customFontSize } ) {
                return typeof customFontSize === 'number';
            },
            migrate( attributes ) {
                return {
                    ...attributes,
                    customFontSize: attributes.customFontSize ? '' + attributes.customFontSize : undefined,
                };
            },
            save( attributes ) {
                return save( attributes );
            }
        }
    ]
};

registerBlockType( 'storeabill/item-meta', settings );