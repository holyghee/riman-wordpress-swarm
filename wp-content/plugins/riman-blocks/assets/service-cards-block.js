(function(blocks, element, components, blockEditor){
  const el = element.createElement;
  const { useState, Fragment } = element;
  const InspectorControls = (blockEditor || wp.blockEditor).InspectorControls;
  const PanelBody = components.PanelBody;
  const RangeControl = components.RangeControl;
  const ToggleControl = components.ToggleControl;
  const TextControl = components.TextControl;
  const SelectControl = components.SelectControl;
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
      useHeroTitle: { type: 'boolean', default: false },
      descriptionSource: { type: 'string', default: 'auto' },
      showFullText: { type: 'boolean', default: false },
      showLearnMoreButton: { type: 'boolean', default: false },
      learnMoreText: { type: 'string', default: 'Mehr erfahren' }
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
                { label: 'RIMAN Seiten (Top‑Level)', value: 'riman' }
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
              label: 'Überlappung nach oben (px)',
              min: 0,
              max: 400,
              step: 5,
              value: attributes.overlapOffset || 0,
              onChange: (v) => setAttributes({ overlapOffset: v || 0 }),
              help: '0 = kein Versatz. Positive Werte ziehen die Service Cards näher an den Hero (Überlappung).'
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
            'source','taxonomy','parent','columns','showDescriptions','showChildren','shape','mobileSlider','sliderAutoplay','sliderInterval','responsiveWidth','responsiveWidthValue','desktopWidth','tabletWidth','mobileWidth','customDesktopWidth','customTabletWidth','customMobileWidth','contentOffset','overlapOffset','useHeroTitle','descriptionSource','showFullText','showLearnMoreButton','learnMoreText'
          ];
          const numberKeys = new Set(['parent','columns','sliderInterval','contentOffset','overlapOffset']);
          const booleanKeys = new Set(['showDescriptions','mobileSlider','sliderAutoplay','responsiveWidth','useHeroTitle','showFullText','showLearnMoreButton']);
          const safe = {};
          allowedKeys.forEach((k)=>{
            const v = attributes[k];
            if (typeof v === 'undefined') return;
            if (numberKeys.has(k)) {
              safe[k] = parseInt(v, 10) || 0;
            } else if (booleanKeys.has(k)) {
              safe[k] = !!v;
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
