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
    BlockControls,
    AlignmentToolbar,
} from '@wordpress/block-editor';

import { PanelBody } from "@wordpress/components";
import { compose } from "@wordpress/compose";
import { FORMAT_TYPES } from '@storeabill/settings';
import { useRef } from "@wordpress/element";
import { getFontSizeStyle, convertFontSizeForPicker, useColors, replacePreviewWithPlaceholder, replacePlaceholderWithPreview } from '@storeabill/utils';

function DeclarationOfOriginEdit( {
    attributes,
    setAttributes,
    fontSize,
    setFontSize,
    className
} ) {
    const { content, align } = attributes;

    const classes = classnames( 'document-declaration-of-origin placeholder-wrapper', className, {
        [ `has-text-align-${ align }` ]: align,
        [ fontSize.class ]: fontSize.class,
    } );

    const origin = 'EU';

    const ref = useRef();

    const {
        TextColor,
        InspectorControlsColorPanel,
    } = useColors(
        [
            { name: 'textColor', property: 'color' },
        ],
        [ fontSize.size ]
    );

    return (
        <>
            <BlockControls>
                <span className="notice notice-warning sab-visibility-notice">{ _x( 'Conditional visibility', 'storeabill-core', 'storeabill' ) }</span>
                <AlignmentToolbar
                    value={ align }
                    onChange={ ( newAlign ) =>
                        setAttributes( { align: newAlign } )
                    }
                />
            </BlockControls>
            <InspectorControls>
                <PanelBody title={ _x( 'Typography', 'storeabill-core', 'storeabill' ) }>
                    <FontSizePicker
                        value={ convertFontSizeForPicker( fontSize.size ) }
                        onChange={ setFontSize }
                    />
                </PanelBody>
            </InspectorControls>
            { InspectorControlsColorPanel }
            <TextColor>
                <RichText
                    tagName="p"
                    value={ replacePlaceholderWithPreview( content, origin, '{content}', false, _x( 'Origin', 'storeabill-core', 'storeabill' ) ) }
                    placeholder=""
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
        </>
    );
}

const DeclarationOfOriginEditWrapper = compose( [ withFontSizes( 'fontSize' ) ] )(
    DeclarationOfOriginEdit
);

export default DeclarationOfOriginEditWrapper;