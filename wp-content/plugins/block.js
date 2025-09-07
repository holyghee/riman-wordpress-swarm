(function(blocks, element, editor, components) {
    var el = element.createElement;
    var InspectorControls = editor.InspectorControls;
    var PanelBody = components.PanelBody;
    var ToggleControl = components.ToggleControl;
    var RangeControl = components.RangeControl;
    var ServerSideRender = wp.serverSideRender;
    
    blocks.registerBlockType('riman/subcategories-carousel', {
        title: 'Unterkategorien Carousel',
        icon: 'images-alt2',
        category: 'widgets',
        attributes: {
            showImages: {
                type: 'boolean',
                default: true
            },
            columns: {
                type: 'number',
                default: 3
            }
        },
        
        edit: function(props) {
            return [
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Carousel Einstellungen' },
                        el(ToggleControl, {
                            label: 'Bilder anzeigen',
                            checked: props.attributes.showImages,
                            onChange: function(value) {
                                props.setAttributes({ showImages: value });
                            }
                        }),
                        el(RangeControl, {
                            label: 'Spalten',
                            value: props.attributes.columns,
                            onChange: function(value) {
                                props.setAttributes({ columns: value });
                            },
                            min: 1,
                            max: 4
                        })
                    )
                ),
                el(ServerSideRender, {
                    block: 'riman/subcategories-carousel',
                    attributes: props.attributes
                })
            ];
        },
        
        save: function() {
            return null; // Server-side render
        }
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor || window.wp.editor,
    window.wp.components
);