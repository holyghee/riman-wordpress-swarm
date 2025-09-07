(function(blocks, element, blockEditor, components) {
  const { registerBlockType } = blocks;
  const { createElement: el, Fragment } = element;
  const { InspectorControls, useBlockProps } = blockEditor;
  const { PanelBody, RangeControl, ToggleControl } = components;

  registerBlockType('riman/category-hero', {
    title: 'RIMAN: Category Hero',
    icon: 'format-image',
    category: 'riman',
    supports: { align: ['full'], html: false },
    attributes: {
      minHeight: { type: 'number', default: 600 },
      dim: { type: 'number', default: 40 },
      showDescription: { type: 'boolean', default: true },
      useLinkedPageTitle: { type: 'boolean', default: true },
      overlapHeader: { type: 'boolean', default: false },
      titleMode: { type: 'string', default: 'category' },
      debug: { type: 'boolean', default: false }
    },
    edit: function(props) {
      const { attributes, setAttributes } = props;
      const blockProps = useBlockProps({ style: { border: '2px dashed #ddd', padding: '20px', background: '#f8f9fa' } });
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Hero Einstellungen', initialOpen: true },
            el(RangeControl, { label: 'Min. Höhe (px)', value: attributes.minHeight, onChange: (v)=>setAttributes({minHeight:v}), min: 300, max: 1000 }),
            el(RangeControl, { label: 'Bild-Dimmung (%)', value: attributes.dim, onChange: (v)=>setAttributes({dim:v}), min: 0, max: 100 }),
            el(ToggleControl, { label: 'Beschreibung anzeigen', checked: attributes.showDescription, onChange: (v)=>setAttributes({showDescription: v}) }),
            el(ToggleControl, { label: 'Seiten‑Titel zusätzlich anzeigen', checked: attributes.useLinkedPageTitle, onChange: (v)=>setAttributes({useLinkedPageTitle: v}) }),
            el(ToggleControl, { label: 'Header überlappen', checked: attributes.overlapHeader, onChange: (v)=>setAttributes({overlapHeader: v}) }),
            el(ToggleControl, { label: 'Debug-Ausgabe', checked: attributes.debug, onChange: (v)=>setAttributes({debug: v}) })
          )
        ),
        el('div', blockProps,
          el('h3', {}, 'RIMAN: Category Hero (Vorschau)'),
          el('p', { style: { color: '#666' } }, 'Wird auf Kategorieseiten mit dem Bild der verknüpften Seite dargestellt.')
        )
      );
    },
    save: function(){ return null; }
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components
);
