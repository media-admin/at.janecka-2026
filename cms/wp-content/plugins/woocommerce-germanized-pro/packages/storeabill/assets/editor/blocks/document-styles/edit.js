/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';
import { compose } from "@wordpress/compose";
import { withDispatch, withSelect } from "@wordpress/data";
import { get, isEqual, isEmpty } from 'lodash';
import { Component } from "@wordpress/element";

import { formatMargins, getSetting, getCurrentFonts, getFontsCSS } from '@storeabill/settings';
import { getFontSizeStyle } from '@storeabill/utils';

class DocumentStylesEdit extends Component {

    constructor() {
        super( ...arguments );

        this.state = {
            fontsFacetsCSS: '',
            fontsInlineCSS: '',
        };

        this.myRef = React.createRef();
    }

    componentDidMount() {
        this.updateFontsCSS();
        this.applyWrapperStyles();
    }

    updateFontsCSS() {
        if ( ! isEmpty( this.props.fonts ) && ! getSetting( 'isFirstPage' ) ) {
            getFontsCSS( this.props.fonts ).then( css => {
                this.setState({
                    'fontsFacetsCSS': css['facets'],
                    'fontsInlineCSS': css['inline'],
                });
            } ).catch( () => {
                this.setState({
                    'fontsFacetsCSS': '',
                    'fontsInlineCSS': '',
                });
            } );
        } else {
            this.setState({
                'fontsFacetsCSS': '',
                'fontsInlineCSS': '',
            });
        }
    }

    componentDidUpdate( prevProps, prevState ) {
        if ( ! isEqual( this.props.fonts, prevProps.fonts ) ) {
            this.updateFontsCSS();
        }

        // After lazy loading fonts, adjust font faces + family
        if ( this.state.fontsFacetsCSS !== prevState.fontsFacetsCSS ) {
            this.addFonts();
        }

        if ( ! isEqual( this.props.fonts, prevProps.fonts ) ||
            ! isEqual( this.props.pdfAttachment, prevProps.pdfAttachment ) ||
            ! isEqual( this.props.margins, prevProps.margins ) ||
            ! isEqual( this.props.fontSize, prevProps.fontSize ) ||
            ! isEqual( this.props.color, prevProps.color )
        ) {
            this.applyWrapperStyles();
        }
    }

    addFonts() {
        const { fontsFacetsCSS, fontsInlineCSS } = this.state;

        if ( ! this.myRef.current) {
            return;
        }

        const { ownerDocument } = this.myRef.current;
        const { defaultView } = ownerDocument;
        const $document = jQuery( defaultView.document );

        if ( $document.find( 'style#sab-block-editor-inline-css' ) <= 0 ) {
            $document.find( 'head' ).append( '<style id="sab-block-editor-inline-css">' );
        }

        let $facetsWrapper = $document.find( 'style#sab-block-editor-inline-css' );
        let existingFacets = $facetsWrapper.html().trim();

        if ( existingFacets !== fontsFacetsCSS ) {
            $facetsWrapper.html( fontsFacetsCSS );
        }

        $document.find( '#sab-block-editor-inline-fonts-inline-css' ).remove();
        $document.find( 'body' ).append( '<style id="sab-block-editor-inline-fonts-inline-css">' + fontsInlineCSS + '</style>' );
    }

    getAttachmentThumb( image, sizeSlug, attribute ) {
        return get( image, [ 'media_details', 'sizes', sizeSlug, attribute ] );
    }

    applyWrapperStyles() {
        const { pdfAttachment, margins, fonts, fontSize, color } = this.props;

        if ( ! this.myRef.current) {
            return;
        }

        const defaultMargins  = getSetting( 'defaultMargins' );
        const formattedMargins = formatMargins( margins, defaultMargins, 'edit' );

        const { ownerDocument } = this.myRef.current;
        const { defaultView } = ownerDocument;

        let $mainWrapper = jQuery( defaultView.document ).find( '.editor-styles-wrapper' );
        let $wrapper     = $mainWrapper.find( '.block-editor-block-list__layout:not(.edit-post-visual-editor__post-title-wrapper):first' );

        if ( $wrapper.length <= 0 ) {
            $wrapper = jQuery( defaultView.document ).find( '.wp-block-post-content' );
        }

        if ( fontSize ) {
            $wrapper.css( 'font-size', getFontSizeStyle( fontSize ) );
        }

        if ( color ) {
            $wrapper.css( 'color', color );
        }

        if ( getSetting( 'isFirstPage' ) ) {
            $wrapper.addClass( 'sab-is-first-page' );
        }

        let hasBackground = false;

        if ( pdfAttachment ) {
            const previewThumb = this.getAttachmentThumb( pdfAttachment, 'full', 'source_url' );

            if ( previewThumb ) {
                $wrapper.css( 'background-image', 'linear-gradient(to bottom, rgba(255,255,255,0.7) 0%,rgba(255,255,255,0.7) 100%), url(' + previewThumb + ')' );
                $wrapper.addClass( 'has-background-image' );

                hasBackground = true;
            }
        }

        if ( ! hasBackground ) {
            $wrapper.css( 'background-image', 'none' );
            $wrapper.removeClass( 'has-background-image' );
        }

        $wrapper.css( 'padding-left', formattedMargins['left'] + 'cm' );
        $wrapper.css( 'padding-right', formattedMargins['right'] + 'cm' );
        $wrapper.css( 'padding-top', formattedMargins['top'] + 'cm' );
        $wrapper.css( 'padding-bottom', formattedMargins['bottom'] + 'cm' );
    }

    render() {
        return <div ref={this.myRef} />;
    }
}

export default compose(
    withSelect( ( select, { attributes } ) => {

        const { getMedia } = select( 'core' );
        const { getEditedPostAttribute } = select( 'core/editor' );

        const meta            = getEditedPostAttribute( 'meta' );
        const pdfAttachmentId = meta['_pdf_template_id'];
        const documentMargins = meta['_margins'];
        const fontSize        = meta['_font_size'];
        const defaultFontSize = getSetting( 'defaultFontSize' );
        const color           = meta['_color'];
        const defaultColor    = getSetting( 'defaultColor' );
        const fonts           = getCurrentFonts();

        const attachment = pdfAttachmentId ? getMedia( pdfAttachmentId ) : null;

        return {
            pdfAttachment: attachment,
            margins: documentMargins,
            fonts: fonts,
            fontSize: fontSize ? fontSize : defaultFontSize,
            color: color ? color : defaultColor
        };
    } )
)( DocumentStylesEdit );