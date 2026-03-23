/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';
import classnames from 'classnames';

import { BlockControls, AlignmentToolbar } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from "@wordpress/components";
import { getPreview, FORMAT_TYPES } from '@storeabill/settings';
import {
    FontSizePicker,
    InspectorControls,
    withFontSizes,
    RichText,
} from '@wordpress/block-editor';

import { replacePreviewWithPlaceholder, replacePlaceholderWithPreview, getFontSizeStyle, convertFontSizeForPicker, useColors } from "@storeabill/utils";
import { compose } from "@wordpress/compose";
const SenderAddressEdit = ( {
  attributes,
  setAttributes,
  fontSize,
  setFontSize,
    className
} ) => {

    const { align, content, heading } = attributes;
    let preview = getPreview();

    const address = preview.formatted_sender_address;

    const {
        TextColor,
        InspectorControlsColorPanel
    } = useColors(
        [
            { name: 'textColor', property: 'color' },
        ],
        [ fontSize.size ]
    );

    const classes = classnames( 'document-sender-address address-wrapper placeholder-wrapper', className, {
        [ `has-text-align-${ align }` ]: align,
        [ fontSize.class ]: fontSize.class,
    } );

    return (
        <>
            <BlockControls>
                <AlignmentToolbar
                    value={ align }
                    onChange={ ( updatedAlignment ) => setAttributes( { align: updatedAlignment } ) }
                />
            </BlockControls>
            <InspectorControls>
                <PanelBody>
                    <FontSizePicker
                        value={ convertFontSizeForPicker( fontSize.size ) }
                        onChange={ setFontSize }
                    />
                </PanelBody>
            </InspectorControls>
            { InspectorControlsColorPanel }
            <div className={ classes }>
                <TextColor>
                    <RichText
                        className="address-heading"
                        value={heading}
                        tagName="p"
                        allowedFormats={FORMAT_TYPES}
                        onChange={(value) =>
                            setAttributes({heading: value})
                        }
                    />
                    <RichText
                        tagName="p"
                        value={ replacePlaceholderWithPreview( content, address, '{content}' ) }
                        placeholder={ replacePlaceholderWithPreview( undefined, address, '{content}' ) }
                        className="sab-address-content placeholder-wrapper"
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

const SenderAddressEditor = compose( [ withFontSizes( 'fontSize' ) ] )(
    SenderAddressEdit
);

export default SenderAddressEditor;