/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';
import classnames from 'classnames';

import {
    FontSizePicker,
    InspectorControls,
    withFontSizes,
    RichText,
} from '@wordpress/block-editor';

import { getPreviewItem, FORMAT_TYPES } from '@storeabill/settings';
import { replacePreviewWithPlaceholder, replacePlaceholderWithPreview, getFontSizeStyle, convertFontSizeForPicker, useColors } from "@storeabill/utils";

import { PanelBody } from "@wordpress/components";
import { compose } from "@wordpress/compose";

const ItemGlobalUniqueIdEdit = ( {
    attributes,
    setAttributes,
    fontSize,
    setFontSize,
    className
} ) => {
    const { content, itemType } = attributes;
    let item = getPreviewItem( itemType );

    const globalUniqueId = item.global_unique_id;

    const classes = classnames( 'sab-block-item-content placeholder-wrapper sab-block-item-global-unique-id', className, {
        [ fontSize.class ]: fontSize.class,
    } );

    const {
        TextColor,
        InspectorControlsColorPanel
    } = useColors(
        [
            { name: 'textColor', property: 'color' },
        ],
        [ fontSize.size ]
    );

    return (
        <>
            <InspectorControls>
                <PanelBody title={ _x( 'Typography', 'storeabill-core', 'storeabill' ) }>
                    <FontSizePicker
                        value={ convertFontSizeForPicker( fontSize.size ) }
                        onChange={ setFontSize }
                    />
                </PanelBody>
            </InspectorControls>
            { InspectorControlsColorPanel }
            <div>
                <TextColor>
                    <RichText
                      tagName="p"
                      value={ replacePlaceholderWithPreview( content, globalUniqueId, '{content}', false, _x( 'Global Unique ID', 'storeabill-core', 'storeabill' ) ) }
                      placeholder={ replacePlaceholderWithPreview( undefined, globalUniqueId, '{content}', false, _x( 'Global Unique ID', 'storeabill-core', 'storeabill' ) ) }
                      className={ classes }
                      onChange={ ( value ) =>
                        setAttributes( { content: replacePreviewWithPlaceholder( value, '{content}' ) } )
                      }
                      allowedFormats={ FORMAT_TYPES }
                      style={ {
                          fontSize: getFontSizeStyle( fontSize )
                      } }
                    />
                </TextColor>
            </div>
        </>
    );
};

const ItemGlobalUniqueIdEditor = compose( [ withFontSizes( 'fontSize' ) ] )(
    ItemGlobalUniqueIdEdit
);

export default ItemGlobalUniqueIdEditor;