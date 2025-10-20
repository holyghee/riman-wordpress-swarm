(function(blocks, element, components, blockEditor){
  const el = element.createElement;
  const { useState, Fragment } = element;
  const InspectorControls = (blockEditor || wp.blockEditor).InspectorControls;
  const MediaUpload = (blockEditor || wp.blockEditor).MediaUpload;
  const MediaUploadCheck = (blockEditor || wp.blockEditor).MediaUploadCheck;
  const PanelBody = components.PanelBody;
  const RangeControl = components.RangeControl;
  const ToggleControl = components.ToggleControl;
  const TextControl = components.TextControl;
  const SelectControl = components.SelectControl;
  const Button = components.Button;
  const ServerSideRender = wp.serverSideRender;




  blocks.registerBlockType('riman/service-cards', {
    title: 'RIMAN Service Cards',
    icon: 'grid-view',
    category: 'riman',
    supports: { align: ['wide','full'] },
    attributes: {
      source: { type: 'string', default: 'category' },
      taxonomy: { type: 'string', default: 'category' },
      parent: { type: 'number', default: 0 },
      columns: { type: 'number', default: 3 },
      showDescriptions: { type: 'boolean', default: true },
      showChildren: { type: 'string', default: 'auto' },
      shape: { type: 'string', default: 'ellipse' },
      mobileSlider: { type: 'boolean', default: false },
      sliderAutoplay: { type: 'boolean', default: false },
      sliderInterval: { type: 'number', default: 5000 },
      responsiveWidth: { type: 'boolean', default: false },
      responsiveWidthValue: { type: 'string', default: '100%' },
      desktopWidth: { type: 'string', default: 'default' },
      tabletWidth: { type: 'string', default: 'default' },
      mobileWidth: { type: 'string', default: 'default' },
      customDesktopWidth: { type: 'string', default: '100%' },
      customTabletWidth: { type: 'string', default: '100%' },
      customMobileWidth: { type: 'string', default: '100%' },
      contentOffset: { type: 'number', default: 0 },
      overlapOffset: { type: 'number', default: 0 },
      overlapOffsetTablet: { type: 'number', default: -1 },
      overlapOffsetMobile: { type: 'number', default: -1 },
      useHeroTitle: { type: 'boolean', default: false },
      descriptionSource: { type: 'string', default: 'auto' },
      showFullText: { type: 'boolean', default: false },
      showLearnMoreButton: { type: 'boolean', default: false },
      learnMoreText: { type: 'string', default: 'Mehr erfahren' },
      customCards: { type: 'array', default: [] }
    },
    edit: function(props){
      const { attributes, setAttributes } = props;

      return [
        el(InspectorControls, {},
          el(PanelBody, { title: 'Einstellungen' },
            el(SelectControl, {
              label: 'Quelle',
              value: attributes.source || 'category',
              options: [
                { label: 'Kategorien', value: 'category' },
                { label: 'RIMAN Seiten (Top‑Level)', value: 'riman' },
                { label: 'Individuell bearbeiten', value: 'custom' }
              ],
              onChange: (v)=> setAttributes({ source: v || 'category' })
            }),
            (attributes.source === 'category') && el(TextControl, {
              label: 'Taxonomie',
              value: attributes.taxonomy,
              onChange: (v) => setAttributes({ taxonomy: v || 'category' })
            }),
            (attributes.source === 'category') && el(TextControl, {
              label: 'Übergeordnete Kategorie (ID)',
              value: String(attributes.parent || 0),
              type: 'number',
              onChange: (v) => setAttributes({ parent: parseInt(v || '0', 10) || 0 })
            }),
            el(RangeControl, {
              label: 'Spalten',
              min: 1,
              max: 4,
              value: attributes.columns,
              onChange: (v) => setAttributes({ columns: v })
            }),
            el(ToggleControl, {
              label: 'Beschreibungen anzeigen',
              checked: !!attributes.showDescriptions,
              onChange: (v) => setAttributes({ showDescriptions: !!v })
            }),
            (attributes.source === 'riman') && el(SelectControl, {
              label: 'Kinder-Seiten anzeigen',
              value: attributes.showChildren || 'auto',
              options: [
                { label: 'Automatisch (Kinder wenn vorhanden)', value: 'auto' },
                { label: 'Immer Kinder-Seiten', value: 'always' },
                { label: 'Niemals Kinder-Seiten', value: 'never' }
              ],
              onChange: (v) => setAttributes({ showChildren: v || 'auto' }),
              help: 'Bestimmt, ob Kinder-Seiten der aktuellen RIMAN Seite angezeigt werden.'
            }),
            (attributes.source === 'riman') && el('div', { style: { marginTop: '16px' } },
              el('h4', { style: { margin: '0 0 8px', fontSize: '14px', fontWeight: '600', color: '#1e1e1e' } }, 'Hero-Section Overrides'),
              el('div', { style: { fontSize: '12px', color: '#666', marginBottom: '12px' } }, 'Verwende Hero-Section Texte anstatt Standard-Seiteninhalte:'),
              el(ToggleControl, {
                label: 'Hero-Titel verwenden (statt Standard-Titel)',
                checked: !!attributes.useHeroTitle,
                onChange: (v) => setAttributes({ useHeroTitle: !!v })
              }),
              el(SelectControl, {
                label: 'Beschreibungstext-Quelle',
                value: attributes.descriptionSource || 'auto',
                options: [
                  { label: 'Automatisch (Hero-Lang → Hero-Kurz → Seiten-Excerpt)', value: 'auto' },
                  { label: 'Hero Langer Text (Fallback: Seiten-Excerpt)', value: 'hero_longtext' },
                  { label: 'Hero-Untertitel (Fallback: Seiten-Excerpt)', value: 'hero_subtitle' },
                  { label: 'Seiten-Excerpt/Content (Standard)', value: 'page_content' }
                ],
                onChange: (v) => setAttributes({ descriptionSource: v || 'auto' }),
                help: 'Wähle die bevorzugte Textquelle. Bei fehlendem Inhalt wird automatisch auf verfügbare Alternativen zurückgegriffen.'
              }),
              el(ToggleControl, {
                label: 'Volltext anzeigen (nicht auf 24 Wörter kürzen)',
                checked: !!attributes.showFullText,
                onChange: (v) => setAttributes({ showFullText: !!v })
              }),
              el('div', { style: { marginTop: '16px' } },
                el(ToggleControl, {
                  label: '"Mehr erfahren" Button anzeigen',
                  checked: !!attributes.showLearnMoreButton,
                  onChange: (v) => setAttributes({ showLearnMoreButton: !!v })
                }),
                (attributes.showLearnMoreButton) && el(TextControl, {
                  label: 'Button-Text',
                  value: attributes.learnMoreText || 'Mehr erfahren',
                  onChange: (v) => setAttributes({ learnMoreText: v || 'Mehr erfahren' }),
                  placeholder: 'Mehr erfahren'
                })
              )
            ),
            (attributes.source === 'custom') && el('div', { style: { marginTop: '16px' } },
              el('h4', { style: { margin: '0 0 12px', fontSize: '14px', fontWeight: '600' } }, 'Service Cards bearbeiten'),
              el('div', { style: { fontSize: '12px', color: '#666', marginBottom: '12px' } }, 'Individuell konfigurierbare Service Cards mit eigenen Inhalten:'),
              el('button', {
                className: 'button button-secondary',
                onClick: () => {
                  const newCard = {
                    title: 'Neue Service Card',
                    description: 'Beschreibung der Service Card',
                    descriptionMode: 'visual',
                    url: '#',
                    image_url: '',
                    image_id: 0,
                    video_url: '',
                    video_id: 0,
                    icon_class: 'fa-solid fa-star',
                    icon_svg: '',
                    icon_id: 0,
                    category_label: 'SERVICE',
                    button_text: 'Mehr erfahren',
                    show_button: true
                  };
                  const currentCards = attributes.customCards || [];
                  setAttributes({ customCards: [...currentCards, newCard] });
                }
              }, '+ Service Card hinzufügen'),
              (attributes.customCards || []).map((card, index) =>
                el('div', {
                  key: index,
                  style: {
                    border: '1px solid #ddd',
                    borderRadius: '4px',
                    padding: '12px',
                    marginTop: '12px',
                    backgroundColor: '#f9f9f9'
                  }
                },
                  el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' } },
                    el('strong', {}, `Service Card ${index + 1}`),
                    el('button', {
                      className: 'button button-link-delete',
                      onClick: () => {
                        const currentCards = [...(attributes.customCards || [])];
                        currentCards.splice(index, 1);
                        setAttributes({ customCards: currentCards });
                      }
                    }, 'Löschen')
                  ),
                  el(TextControl, {
                    label: 'Titel',
                    value: card.title || '',
                    onChange: (value) => {
                      const currentCards = [...(attributes.customCards || [])];
                      currentCards[index] = { ...currentCards[index], title: value };
                      setAttributes({ customCards: currentCards });
                    }
                  }),

                  // Hybrid Rich-Text Editor for Description
                  el('div', {
                    className: 'service-card-hybrid-editor',
                    style: { marginBottom: '12px' }
                  },
                    el('label', {
                      style: { display: 'block', marginBottom: '4px', fontWeight: '600' }
                    }, 'Beschreibung'),

                    // Editor Mode Toggle
                    el('div', {
                      className: 'editor-mode-toggle',
                      style: { marginBottom: '5px' }
                    },
                      el('button', {
                        type: 'button',
                        className: `mode-btn ${(!card.descriptionMode || card.descriptionMode === 'visual') ? 'active' : ''}`,
                        onClick: () => {
                          const currentCards = [...(attributes.customCards || [])];
                          currentCards[index] = { ...currentCards[index], descriptionMode: 'visual' };
                          setAttributes({ customCards: currentCards });
                        },
                        style: {
                          padding: '4px 8px',
                          fontSize: '11px',
                          border: '1px solid #ccc',
                          background: (!card.descriptionMode || card.descriptionMode === 'visual') ? '#0073aa' : '#fff',
                          color: (!card.descriptionMode || card.descriptionMode === 'visual') ? '#fff' : '#000',
                          borderRadius: '3px 0 0 3px',
                          cursor: 'pointer'
                        }
                      }, 'Visuell'),
                      el('button', {
                        type: 'button',
                        className: `mode-btn ${card.descriptionMode === 'html' ? 'active' : ''}`,
                        onClick: () => {
                          const currentCards = [...(attributes.customCards || [])];
                          currentCards[index] = { ...currentCards[index], descriptionMode: 'html' };
                          setAttributes({ customCards: currentCards });
                        },
                        style: {
                          padding: '4px 8px',
                          fontSize: '11px',
                          border: '1px solid #ccc',
                          borderLeft: 'none',
                          background: card.descriptionMode === 'html' ? '#0073aa' : '#fff',
                          color: card.descriptionMode === 'html' ? '#fff' : '#000',
                          borderRadius: '0 3px 3px 0',
                          cursor: 'pointer'
                        }
                      }, 'HTML')
                    ),

                    // Editor Toolbar (only visible in visual mode)
                    (!card.descriptionMode || card.descriptionMode === 'visual') && el('div', {
                      className: 'editor-toolbar',
                      style: {
                        background: '#f9f9f9',
                        border: '1px solid #ddd',
                        borderBottom: 'none',
                        padding: '5px',
                        display: 'flex',
                        gap: '3px',
                        borderRadius: '3px 3px 0 0'
                      }
                    },
                      el('button', {
                        type: 'button',
                        onClick: () => {
                          const currentCards = [...(attributes.customCards || [])];
                          const currentDesc = currentCards[index].description || '';
                          const newDesc = `<strong>Fett</strong> ${currentDesc}`;
                          currentCards[index] = { ...currentCards[index], description: newDesc };
                          setAttributes({ customCards: currentCards });
                        },
                        style: { padding: '3px 6px', fontSize: '11px', border: '1px solid #ccc', background: '#fff', borderRadius: '2px', cursor: 'pointer' },
                        title: 'Fett'
                      }, el('strong', {}, 'B')),

                      el('button', {
                        type: 'button',
                        onClick: () => {
                          const currentCards = [...(attributes.customCards || [])];
                          const currentDesc = currentCards[index].description || '';
                          const newDesc = `<em>Kursiv</em> ${currentDesc}`;
                          currentCards[index] = { ...currentCards[index], description: newDesc };
                          setAttributes({ customCards: currentCards });
                        },
                        style: { padding: '3px 6px', fontSize: '11px', border: '1px solid #ccc', background: '#fff', borderRadius: '2px', cursor: 'pointer' },
                        title: 'Kursiv'
                      }, el('em', {}, 'I')),

                      el('span', { style: { margin: '0 3px', color: '#ccc' } }, '|'),

                      el('button', {
                        type: 'button',
                        onClick: () => {
                          const currentCards = [...(attributes.customCards || [])];
                          const currentDesc = currentCards[index].description || '';
                          const newDesc = `<p style="text-align: justify;">Blocksatz Text</p> ${currentDesc}`;
                          currentCards[index] = { ...currentCards[index], description: newDesc };
                          setAttributes({ customCards: currentCards });
                        },
                        style: { padding: '3px 6px', fontSize: '11px', border: '1px solid #ccc', background: '#fff', borderRadius: '2px', cursor: 'pointer' },
                        title: 'Blocksatz'
                      }, '≡'),

                      el('button', {
                        type: 'button',
                        onClick: () => {
                          const currentCards = [...(attributes.customCards || [])];
                          const currentDesc = currentCards[index].description || '';
                          const newDesc = `<ul><li>Listeneintrag</li></ul> ${currentDesc}`;
                          currentCards[index] = { ...currentCards[index], description: newDesc };
                          setAttributes({ customCards: currentCards });
                        },
                        style: { padding: '3px 6px', fontSize: '11px', border: '1px solid #ccc', background: '#fff', borderRadius: '2px', cursor: 'pointer' },
                        title: 'Liste'
                      }, '•')
                    ),

                    // Visual Editor
                    (!card.descriptionMode || card.descriptionMode === 'visual') && el('div', {
                      contentEditable: true,
                      suppressContentEditableWarning: true,
                      onInput: (e) => {
                        const currentCards = [...(attributes.customCards || [])];
                        currentCards[index] = { ...currentCards[index], description: e.target.innerHTML };
                        setAttributes({ customCards: currentCards });
                      },
                      dangerouslySetInnerHTML: { __html: card.description || '' },
                      style: {
                        minHeight: '80px',
                        padding: '8px',
                        border: '1px solid #ddd',
                        borderTop: 'none',
                        borderRadius: '0 0 3px 3px',
                        background: '#fff',
                        fontSize: '13px',
                        lineHeight: '1.5'
                      },
                      placeholder: 'Beschreibung eingeben...'
                    }),

                    // HTML Editor
                    (card.descriptionMode === 'html') && el('textarea', {
                      placeholder: 'HTML-Code eingeben...',
                      value: card.description || '',
                      onChange: (e) => {
                        const currentCards = [...(attributes.customCards || [])];
                        currentCards[index] = { ...currentCards[index], description: e.target.value };
                        setAttributes({ customCards: currentCards });
                      },
                      style: {
                        width: '100%',
                        minHeight: '80px',
                        padding: '8px',
                        border: '1px solid #ddd',
                        borderRadius: '0 0 3px 3px',
                        fontFamily: 'Consolas, Monaco, "Courier New", monospace',
                        fontSize: '12px',
                        resize: 'vertical'
                      }
                    }),

                    // Help Text
                    el('div', {
                      style: {
                        fontSize: '11px',
                        color: '#666',
                        fontStyle: 'italic',
                        marginTop: '4px',
                        padding: '4px 8px',
                        background: '#f9f9f9',
                        borderRadius: '3px'
                      }
                    },
                      (!card.descriptionMode || card.descriptionMode === 'visual')
                        ? 'Text normal eingeben, Formatierung mit Buttons. HTML wird automatisch erstellt.'
                        : 'HTML-Code direkt bearbeiten. Wechseln Sie zu "Visuell" für normale Textbearbeitung.'
                    )
                  ),
                  el(TextControl, {
                    label: 'Link-URL',
                    value: card.url || '',
                    onChange: (value) => {
                      const currentCards = [...(attributes.customCards || [])];
                      currentCards[index] = { ...currentCards[index], url: value };
                      setAttributes({ customCards: currentCards });
                    }
                  }),
                  el('div', { style: { marginBottom: '12px' } },
                    el('label', { style: { display: 'block', marginBottom: '4px', fontWeight: '600' } }, 'Bild'),
                    el(MediaUploadCheck, {},
                      el(MediaUpload, {
                        onSelect: (media) => {
                          const currentCards = [...(attributes.customCards || [])];
                          currentCards[index] = {
                            ...currentCards[index],
                            image_id: media.id,
                            image_url: media.url
                          };
                          setAttributes({ customCards: currentCards });
                        },
                        allowedTypes: ['image'],
                        value: card.image_id || 0,
                        render: ({ open }) => (
                          el('div', { style: { display: 'flex', gap: '8px', alignItems: 'center' } },
                            el(Button, {
                              onClick: open,
                              className: 'button button-secondary',
                              style: { marginRight: '8px' }
                            }, card.image_url ? 'Bild ändern' : 'Bild auswählen'),
                            card.image_url && el('img', {
                              src: card.image_url,
                              style: { maxWidth: '60px', maxHeight: '40px', objectFit: 'cover', borderRadius: '4px' }
                            }),
                            card.image_url && el(Button, {
                              onClick: () => {
                                const currentCards = [...(attributes.customCards || [])];
                                currentCards[index] = {
                                  ...currentCards[index],
                                  image_id: 0,
                                  image_url: ''
                                };
                                setAttributes({ customCards: currentCards });
                              },
                              className: 'button button-link-delete',
                              style: { marginLeft: '8px' }
                            }, 'Entfernen')
                          )
                        )
                      })
                    )
                  ),
                  el('div', { style: { marginBottom: '12px' } },
                    el('label', { style: { display: 'block', marginBottom: '4px', fontWeight: '600' } }, 'Video (optional)'),
                    el(MediaUploadCheck, {},
                      el(MediaUpload, {
                        onSelect: (media) => {
                          const currentCards = [...(attributes.customCards || [])];
                          currentCards[index] = {
                            ...currentCards[index],
                            video_id: media.id,
                            video_url: media.url
                          };
                          setAttributes({ customCards: currentCards });
                        },
                        allowedTypes: ['video'],
                        value: card.video_id || 0,
                        render: ({ open }) => (
                          el('div', { style: { display: 'flex', gap: '8px', alignItems: 'center' } },
                            el(Button, {
                              onClick: open,
                              className: 'button button-secondary',
                              style: { marginRight: '8px' }
                            }, card.video_url ? 'Video ändern' : 'Video auswählen'),
                            card.video_url && el('div', {
                              style: { fontSize: '12px', color: '#666', maxWidth: '200px' }
                            }, card.video_url.split('/').pop()),
                            card.video_url && el(Button, {
                              onClick: () => {
                                const currentCards = [...(attributes.customCards || [])];
                                currentCards[index] = {
                                  ...currentCards[index],
                                  video_id: 0,
                                  video_url: ''
                                };
                                setAttributes({ customCards: currentCards });
                              },
                              className: 'button button-link-delete',
                              style: { marginLeft: '8px' }
                            }, 'Entfernen')
                          )
                        )
                      })
                    )
                  ),
                  el(TextControl, {
                    label: 'Icon-Klasse (z.B. fa-solid fa-star)',
                    value: card.icon_class || '',
                    onChange: (value) => {
                      const currentCards = [...(attributes.customCards || [])];
                      currentCards[index] = { ...currentCards[index], icon_class: value };
                      setAttributes({ customCards: currentCards });
                    }
                  }),
                  el(TextControl, {
                    label: 'Bereichs-Label',
                    value: card.category_label || '',
                    onChange: (value) => {
                      const currentCards = [...(attributes.customCards || [])];
                      currentCards[index] = { ...currentCards[index], category_label: value };
                      setAttributes({ customCards: currentCards });
                    },
                    placeholder: 'SERVICE'
                  }),
                  el('div', { style: { marginTop: '12px', paddingTop: '12px', borderTop: '1px solid #ddd' } },
                    el('h4', { style: { margin: '0 0 8px', fontSize: '13px', fontWeight: '600' } }, 'Call-to-Action Button'),
                    el(ToggleControl, {
                      label: 'Button anzeigen',
                      checked: card.show_button !== false,
                      onChange: (value) => {
                        const currentCards = [...(attributes.customCards || [])];
                        currentCards[index] = { ...currentCards[index], show_button: value };
                        setAttributes({ customCards: currentCards });
                      }
                    }),
                    (card.show_button !== false) && el(TextControl, {
                      label: 'Button-Text',
                      value: card.button_text || 'Mehr erfahren',
                      onChange: (value) => {
                        const currentCards = [...(attributes.customCards || [])];
                        currentCards[index] = { ...currentCards[index], button_text: value };
                        setAttributes({ customCards: currentCards });
                      },
                      placeholder: 'Mehr erfahren'
                    })
                  )
                )
              )
            )
          ),
          el(PanelBody, { title: 'Erscheinungsbild', initialOpen: false },
            el(SelectControl, {
              label: 'Schnittmaske (Shape)',
              value: attributes.shape || 'ellipse',
              options: [
                { label: 'Ellipse (Standard)', value: 'ellipse' },
                { label: 'RIMAN Welle', value: 'wave' },
                { label: 'Keine', value: 'none' }
              ],
              onChange: (v) => setAttributes({ shape: v })
            }),
            el(RangeControl, {
              label: 'Inhalt nach unten versetzen (px)',
              min: 0,
              max: 100,
              step: 5,
              value: attributes.contentOffset || 0,
              onChange: (v) => setAttributes({ contentOffset: v || 0 }),
              help: 'Verschiebt den Inhalt (ab Icon) nach unten. 0 = Standard-Position.'
            }),
            el(RangeControl, {
              label: 'Desktop Überlappung nach oben (px)',
              min: 0,
              max: 400,
              step: 5,
              value: attributes.overlapOffset || 0,
              onChange: (v) => setAttributes({ overlapOffset: v || 0 }),
              help: 'Desktop (≥1200px): 0 = kein Versatz. Positive Werte ziehen die Service Cards näher an den Hero.'
            }),
            el(RangeControl, {
              label: 'Tablet Überlappung nach oben (px)',
              min: -1,
              max: 400,
              step: 1,
              value: (attributes.overlapOffsetTablet !== undefined) ? attributes.overlapOffsetTablet : -1,
              onChange: (v) => setAttributes({ overlapOffsetTablet: v }),
              help: 'Tablet (768-1199px): -1 = Desktop-Wert verwenden, 0 = kein Versatz, 1+ = Überlappung in px'
            }),
            el(RangeControl, {
              label: 'Mobile Überlappung nach oben (px)',
              min: -1,
              max: 400,
              step: 1,
              value: (attributes.overlapOffsetMobile !== undefined) ? attributes.overlapOffsetMobile : -1,
              onChange: (v) => setAttributes({ overlapOffsetMobile: v }),
              help: 'Mobile (≤767px): -1 = Desktop-Wert verwenden, 0 = kein Versatz, 1+ = Überlappung in px'
            })
          ),
          el(PanelBody, { title: 'Mobile Slider', initialOpen: false },
            el(ToggleControl, {
              label: 'Mobile Slider aktivieren',
              checked: !!attributes.mobileSlider,
              onChange: (v) => setAttributes({ mobileSlider: !!v })
            }),
            (attributes.mobileSlider) && el(ToggleControl, {
              label: 'Auto-Play',
              checked: !!attributes.sliderAutoplay,
              onChange: (v) => setAttributes({ sliderAutoplay: !!v })
            }),
            (attributes.mobileSlider && attributes.sliderAutoplay) && el(RangeControl, {
              label: 'Intervall (ms)',
              min: 1000,
              max: 10000,
              step: 500,
              value: attributes.sliderInterval || 5000,
              onChange: (v) => setAttributes({ sliderInterval: v })
            })
          ),
          el(PanelBody, { title: 'Responsive Breite', initialOpen: false },
            el('h4', { style: { margin: '16px 0 8px', fontSize: '14px', fontWeight: '600' } }, '🖥️ Desktop (1025px+)'),
            el(SelectControl, {
              label: 'Desktop Container-Breite',
              value: attributes.desktopWidth || 'default',
              options: [
                { label: 'Standard (Container)', value: 'default' },
                { label: 'Breite Weite (Wide)', value: 'wide' },
                { label: 'Vollbreite (Full Width)', value: 'full' },
                { label: 'Benutzerdefiniert', value: 'custom' }
              ],
              onChange: (v) => setAttributes({ desktopWidth: v || 'default' })
            }),
            (attributes.desktopWidth === 'custom') && el(TextControl, {
              label: 'Desktop Breite (z.B. 1200px, 90vw)',
              value: attributes.customDesktopWidth || '100%',
              onChange: (v) => setAttributes({ customDesktopWidth: v || '100%' })
            }),

            el('h4', { style: { margin: '16px 0 8px', fontSize: '14px', fontWeight: '600' } }, '📱 Tablet (781px - 1024px)'),
            el(SelectControl, {
              label: 'Tablet Container-Breite',
              value: attributes.tabletWidth || 'default',
              options: [
                { label: 'Standard (Container)', value: 'default' },
                { label: 'Breite Weite (Wide)', value: 'wide' },
                { label: 'Vollbreite (Full Width)', value: 'full' },
                { label: 'Benutzerdefiniert', value: 'custom' }
              ],
              onChange: (v) => setAttributes({ tabletWidth: v || 'default' })
            }),
            (attributes.tabletWidth === 'custom') && el(TextControl, {
              label: 'Tablet Breite (z.B. 100%, 95vw)',
              value: attributes.customTabletWidth || '100%',
              onChange: (v) => setAttributes({ customTabletWidth: v || '100%' })
            }),

            el('h4', { style: { margin: '16px 0 8px', fontSize: '14px', fontWeight: '600' } }, '📱 Mobile (≤780px)'),
            el(SelectControl, {
              label: 'Mobile Container-Breite',
              value: attributes.mobileWidth || 'default',
              options: [
                { label: 'Standard (Container)', value: 'default' },
                { label: 'Breite Weite (Wide)', value: 'wide' },
                { label: 'Vollbreite (Full Width)', value: 'full' },
                { label: 'Benutzerdefiniert', value: 'custom' }
              ],
              onChange: (v) => setAttributes({ mobileWidth: v || 'default' })
            }),
            (attributes.mobileWidth === 'custom') && el(TextControl, {
              label: 'Mobile Breite (z.B. 100vw, calc(100% - 32px))',
              value: attributes.customMobileWidth || '100%',
              onChange: (v) => setAttributes({ customMobileWidth: v || '100%' })
            })
          )
        ),
        (function(){
          // Keep SSR payload minimal to avoid REST validation issues
          const allowedKeys = [
            'source','taxonomy','parent','columns','showDescriptions','showChildren','shape','mobileSlider','sliderAutoplay','sliderInterval','responsiveWidth','responsiveWidthValue','desktopWidth','tabletWidth','mobileWidth','customDesktopWidth','customTabletWidth','customMobileWidth','contentOffset','overlapOffset','overlapOffsetTablet','overlapOffsetMobile','useHeroTitle','descriptionSource','showFullText','showLearnMoreButton','learnMoreText','customCards'
          ];
          const numberKeys = new Set(['parent','columns','sliderInterval','contentOffset','overlapOffset','overlapOffsetTablet','overlapOffsetMobile']);
          const booleanKeys = new Set(['showDescriptions','mobileSlider','sliderAutoplay','responsiveWidth','useHeroTitle','showFullText','showLearnMoreButton']);
          const arrayKeys = new Set(['customCards']);
          const safe = {};
          allowedKeys.forEach((k)=>{
            const v = attributes[k];
            if (typeof v === 'undefined') return;
            if (numberKeys.has(k)) {
              safe[k] = parseInt(v, 10) || 0;
            } else if (booleanKeys.has(k)) {
              safe[k] = !!v;
            } else if (arrayKeys.has(k)) {
              safe[k] = Array.isArray(v) ? v : [];
            } else {
              safe[k] = String(v || '');
            }
          });
          return el(ServerSideRender, { block: 'riman/service-cards', attributes: safe, httpMethod: 'POST' });
        })()
      ];
    },
    save: function(){
      return null; // Server-side rendered block
    }
  });

})(
  window.wp.blocks,
  window.wp.element,
  window.wp.components,
  window.wp.blockEditor
);
