(function (wp) {
    const { addFilter } = wp.hooks;
    const { __ } = wp.i18n;
    const { Fragment } = wp.element;
    const { PanelBody, Button } = wp.components;
    const { InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor || wp.editor;
    const { createHigherOrderComponent } = wp.compose;

    const TARGET_BLOCK = 'core/cover';

    addFilter('blocks.registerBlockType', 'riman/cover-poster/attributes', (settings, name) => {
        if (name !== TARGET_BLOCK) {
            return settings;
        }

        settings.attributes = Object.assign({}, settings.attributes, {
            rimanPosterId: {
                type: 'number',
                default: 0,
            },
            rimanPosterUrl: {
                type: 'string',
                default: '',
            },
        });

        return settings;
    });

    const withPosterControls = createHigherOrderComponent((BlockEdit) => {
        return (props) => {
            if (props.name !== TARGET_BLOCK || props.attributes.backgroundType !== 'video') {
                return wp.element.createElement(BlockEdit, props);
            }

            const { rimanPosterId, rimanPosterUrl } = props.attributes;

            const onSelectPoster = (media) => {
                if (!media || !media.url) {
                    props.setAttributes({ rimanPosterId: 0, rimanPosterUrl: '' });
                    return;
                }

                props.setAttributes({
                    rimanPosterId: media.id || 0,
                    rimanPosterUrl: media.url,
                });
            };

            const onRemovePoster = () => {
                props.setAttributes({ rimanPosterId: 0, rimanPosterUrl: '' });
            };

            return wp.element.createElement(
                Fragment,
                null,
                wp.element.createElement(BlockEdit, props),
                wp.element.createElement(
                    InspectorControls,
                    null,
                    wp.element.createElement(
                        PanelBody,
                        {
                            title: __('Posterbild', 'riman'),
                            initialOpen: false,
                        },
                        wp.element.createElement(
                            MediaUploadCheck,
                            null,
                            wp.element.createElement(MediaUpload, {
                                onSelect: onSelectPoster,
                                allowedTypes: ['image'],
                                value: rimanPosterId,
                                render: ({ open }) =>
                                    wp.element.createElement(
                                        Button,
                                        {
                                            variant: 'secondary',
                                            onClick: open,
                                        },
                                        rimanPosterUrl
                                            ? __('Posterbild ersetzen', 'riman')
                                            : __('Posterbild auswählen', 'riman')
                                    ),
                            })
                        ),
                        rimanPosterUrl
                            ? wp.element.createElement(
                                  Fragment,
                                  null,
                                  wp.element.createElement('div', {
                                      style: {
                                          marginTop: '12px',
                                          border: '1px solid rgba(0,0,0,0.1)',
                                          borderRadius: '4px',
                                          overflow: 'hidden',
                                      },
                                      children: wp.element.createElement('img', {
                                          src: rimanPosterUrl,
                                          alt: __('Posterbild Vorschau', 'riman'),
                                          style: { width: '100%', display: 'block' },
                                      }),
                                  }),
                                  wp.element.createElement(
                                      Button,
                                      {
                                          variant: 'link',
                                          isDestructive: true,
                                          onClick: onRemovePoster,
                                          style: { marginTop: '8px', paddingLeft: 0 },
                                      },
                                      __('Poster entfernen', 'riman')
                                  )
                              )
                            : null
                    )
                )
            );
        };
    }, 'withRimanPosterControls');

    addFilter('editor.BlockEdit', 'riman/cover-poster/controls', withPosterControls);
})(window.wp);
