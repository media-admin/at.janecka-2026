( function( blocks, element, blockEditor, components ) {
    var el = element.createElement;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var RangeControl = components.RangeControl;

    blocks.registerBlockType( 'janecka/featured-products', {
        edit: function( props ) {
            return [
                el( InspectorControls, { key: 'inspector' },
                    el( PanelBody, { title: 'Einstellungen', initialOpen: true },
                        el( RangeControl, {
                            label: 'Anzahl Produkte',
                            value: props.attributes.count,
                            onChange: function( val ) { props.setAttributes( { count: val } ); },
                            min: 1,
                            max: 8
                        } )
                    )
                ),
                el( 'div', { key: 'preview', style: { padding: '1rem', background: '#f5f5f5', border: '1px solid #ddd', textAlign: 'center' } },
                    el( 'span', { style: { fontSize: '1.5rem', marginRight: '0.5rem' } }, '⭐' ),
                    el( 'strong', {}, 'Hervorgehobene Produkte' ),
                    el( 'p', { style: { margin: '0.5rem 0 0', color: '#666', fontSize: '0.875rem' } },
                        props.attributes.count + ' hervorgehobene Produkte werden angezeigt'
                    )
                )
            ];
        },
        save: function() {
            return null; // Server-side rendered
        }
    } );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components );
