/**
 * RIMAN Page Hero Block Editor Script
 */
(function() {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, RangeControl, ToggleControl, SelectControl } = wp.components;
    const { __ } = wp.i18n;

    registerBlockType('riman/page-hero', {
        title: __('RIMAN Page Hero', 'riman'),
        description: __('Hero-Section für RIMAN Seiten mit Video/Bild, Titel und Beschreibung', 'riman'),
        icon: 'cover-image',
        category: 'design',
        supports: {
            align: ['full'],
        },
        attributes: {
            minHeight: {
                type: 'number',
                default: 500
            },
            dim: {
                type: 'number',
                default: 30
            },
            showDescription: {
                type: 'boolean',
                default: true
            },
            showExcerpt: {
                type: 'boolean',
                default: true
            },
            overlapHeader: {
                type: 'boolean',
                default: false
            },
            titleSize: {
                type: 'string',
                default: 'large'
            },
            textAlign: {
                type: 'string',
                default: 'center'
            },
            contentWidth: {
                type: 'string',
                default: 'content'
            },
            debug: {
                type: 'boolean',
                default: false
            }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const {
                minHeight,
                dim,
                showDescription,
                showExcerpt,
                overlapHeader,
                titleSize,
                textAlign,
                contentWidth,
                debug
            } = attributes;

            return wp.element.createElement(
                'div',
                null,
                // Inspector Controls
                wp.element.createElement(
                    InspectorControls,
                    null,
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Hero Einstellungen', 'riman'), initialOpen: true },
                        wp.element.createElement(RangeControl, {
                            label: __('Mindesthöhe (px)', 'riman'),
                            value: minHeight,
                            onChange: function(value) { setAttributes({ minHeight: value }); },
                            min: 200,
                            max: 1000,
                            step: 50
                        }),
                        wp.element.createElement(RangeControl, {
                            label: __('Overlay-Verdunkelung (%)', 'riman'),
                            value: dim,
                            onChange: function(value) { setAttributes({ dim: value }); },
                            min: 0,
                            max: 80,
                            step: 5
                        }),
                        wp.element.createElement(ToggleControl, {
                            label: __('Header überlappen', 'riman'),
                            checked: overlapHeader,
                            onChange: function(value) { setAttributes({ overlapHeader: value }); }
                        })
                    ),
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Inhalt', 'riman'), initialOpen: true },
                        wp.element.createElement(SelectControl, {
                            label: __('Titel-Größe', 'riman'),
                            value: titleSize,
                            onChange: function(value) { setAttributes({ titleSize: value }); },
                            options: [
                                { label: __('Klein', 'riman'), value: 'small' },
                                { label: __('Mittel', 'riman'), value: 'medium' },
                                { label: __('Groß', 'riman'), value: 'large' },
                                { label: __('Extra Groß', 'riman'), value: 'xlarge' }
                            ]
                        }),
                        wp.element.createElement(ToggleControl, {
                            label: __('Beschreibung anzeigen', 'riman'),
                            checked: showDescription,
                            onChange: function(value) { setAttributes({ showDescription: value }); }
                        }),
                        wp.element.createElement(ToggleControl, {
                            label: __('Excerpt als Fallback', 'riman'),
                            checked: showExcerpt,
                            onChange: function(value) { setAttributes({ showExcerpt: value }); }
                        })
                    ),
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Layout', 'riman'), initialOpen: false },
                        wp.element.createElement(SelectControl, {
                            label: __('Text-Ausrichtung', 'riman'),
                            value: textAlign,
                            onChange: function(value) { setAttributes({ textAlign: value }); },
                            options: [
                                { label: __('Links', 'riman'), value: 'left' },
                                { label: __('Zentriert', 'riman'), value: 'center' },
                                { label: __('Rechts', 'riman'), value: 'right' }
                            ]
                        }),
                        wp.element.createElement(SelectControl, {
                            label: __('Inhalts-Breite', 'riman'),
                            value: contentWidth,
                            onChange: function(value) { setAttributes({ contentWidth: value }); },
                            options: [
                                { label: __('Normal', 'riman'), value: 'content' },
                                { label: __('Breit', 'riman'), value: 'wide' },
                                { label: __('Vollbreite', 'riman'), value: 'full' }
                            ]
                        })
                    ),
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Erweitert', 'riman'), initialOpen: false },
                        wp.element.createElement(ToggleControl, {
                            label: __('Debug-Modus', 'riman'),
                            checked: debug,
                            onChange: function(value) { setAttributes({ debug: value }); }
                        })
                    )
                ),
                // Editor Preview
                wp.element.createElement(
                    'div',
                    {
                        style: {
                            minHeight: minHeight + 'px',
                            background: 'linear-gradient(rgba(0,0,0,' + (dim/100) + '), rgba(0,0,0,' + (dim/100) + ')), linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                            backgroundSize: 'cover',
                            backgroundPosition: 'center',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: textAlign === 'left' ? 'flex-start' : (textAlign === 'right' ? 'flex-end' : 'center'),
                            padding: '2rem',
                            color: '#fff',
                            textAlign: textAlign,
                            position: 'relative'
                        }
                    },
                    wp.element.createElement(
                        'div',
                        {
                            style: {
                                maxWidth: contentWidth === 'content' ? '800px' : (contentWidth === 'wide' ? '1200px' : '100%'),
                                margin: '0 auto'
                            }
                        },
                        wp.element.createElement(
                            'h1',
                            {
                                style: {
                                    fontSize: titleSize === 'small' ? '1.5rem' :
                                             (titleSize === 'medium' ? '2rem' :
                                             (titleSize === 'large' ? '2.5rem' : '3rem')),
                                    marginBottom: '0.5em',
                                    color: '#fff'
                                }
                            },
                            __('Seitentitel (Vorschau)', 'riman')
                        ),
                        showDescription && wp.element.createElement(
                            'p',
                            {
                                style: {
                                    fontSize: '1.1rem',
                                    lineHeight: '1.6',
                                    opacity: '0.9',
                                    color: '#fff'
                                }
                            },
                            __('Hero-Beschreibung oder Excerpt wird hier angezeigt.', 'riman')
                        )
                    )
                )
            );
        },

        save: function() {
            // Dynamic block - save returns null
            return null;
        }
    });
})();