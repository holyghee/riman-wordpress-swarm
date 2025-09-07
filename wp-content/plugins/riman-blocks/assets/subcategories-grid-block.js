/* global wp */
(function(blocks, element, blockEditor, components, data, i18n) {
  const { registerBlockType } = blocks;
  const { createElement: el, Fragment } = element;
  const { InspectorControls, useBlockProps } = blockEditor;
  const { PanelBody, ToggleControl, RangeControl } = components;
  const { __ } = i18n;

  registerBlockType('riman/subcategories-grid', {
    title: __('Unterkategorien', 'riman'),
    description: __('Zeigt alle Unterkategorien der aktuellen Kategorie an', 'riman'),
    category: 'riman',
    icon: 'grid-view',
    supports: { align: ['wide','full'], html: false, customClassName: true },
    attributes: {
      showPosts: { type: 'boolean', default: true },
      postsPerCategory: { type: 'number', default: 3 },
      showDescription: { type: 'boolean', default: true },
      showPostCount: { type: 'boolean', default: true },
      columns: { type: 'number', default: 3 },
      showEmptyCategories: { type: 'boolean', default: true }
    },
    edit: function(props) {
      const { attributes, setAttributes } = props;
      const blockProps = useBlockProps();
      const previewStyle = { padding: '20px', background: '#f8f9fa', borderRadius: '4px', border: '2px dashed #ddd' };
      const gridStyle = { display: 'grid', gridTemplateColumns: `repeat(${attributes.columns}, 1fr)`, gap: '20px', marginTop: '20px' };
      const cardStyle = { background: 'white', padding: '15px', borderRadius: '4px', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' };

      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: __('Anzeige-Einstellungen', 'riman'), initialOpen: true },
            el(RangeControl, { label: __('Spalten', 'riman'), value: attributes.columns, onChange: v=>setAttributes({columns:v}), min:1, max:4 }),
            el(ToggleControl, { label: __('Posts anzeigen', 'riman'), checked: attributes.showPosts, onChange: v=>setAttributes({showPosts:v}) }),
            attributes.showPosts && el(RangeControl, { label: __('Posts pro Kategorie', 'riman'), value: attributes.postsPerCategory, onChange: v=>setAttributes({postsPerCategory:v}), min:1, max:10 }),
            el(ToggleControl, { label: __('Beschreibung anzeigen', 'riman'), checked: attributes.showDescription, onChange: v=>setAttributes({showDescription:v}) }),
            el(ToggleControl, { label: __('Beitragszahl anzeigen', 'riman'), checked: attributes.showPostCount, onChange: v=>setAttributes({showPostCount:v}) }),
            el(ToggleControl, { label: __('Leere Kategorien anzeigen', 'riman'), checked: attributes.showEmptyCategories, onChange: v=>setAttributes({showEmptyCategories:v}) })
          )
        ),
        el('div', blockProps,
          el('div', { style: previewStyle },
            el('h3', {}, __('Unterkategorien Vorschau', 'riman')),
            el('p', { style: { color: '#666', marginTop: '10px' } }, __('Die Unterkategorien der aktuellen Kategorie werden hier angezeigt.', 'riman')),
            el('div', { style: gridStyle },
              Array.from({ length: attributes.columns }).map((_, index) => el('div', { key:index, style: cardStyle },
                el('h4', { style: { margin: '0 0 10px 0' } }, __('Beispiel-Kategorie ', 'riman') + (index+1)),
                attributes.showDescription && el('p', { style: { fontSize: '14px', color: '#666' } }, __('Kategorie-Beschreibung','riman')),
                attributes.showPostCount && el('span', { style: { fontSize:'12px', background:'#f0f0f0', padding:'2px 8px', borderRadius:'10px', display:'inline-block', marginTop:'10px' } }, '3 Beiträge')
              ))
            )
          )
        )
      );
    },
    save: function(){ return null; }
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components,
  window.wp.data,
  window.wp.i18n
);

