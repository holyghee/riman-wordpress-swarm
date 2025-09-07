(function(blocks, element, blockEditor, components) {
  const { registerBlockType } = blocks;
  const { createElement: el, Fragment } = element;
  const { InspectorControls, useBlockProps } = blockEditor;
  const { PanelBody, TextControl, TextareaControl } = components;

  registerBlockType('riman/category-header', {
    title: 'Kategorie-Überschrift',
    icon: 'heading',
    category: 'riman',
    description: 'Zeigt eine dynamische Überschrift mit Kategorienamen an',
    supports: { align: ['wide', 'full'], html: false },
    attributes: {
      label: { type: 'string', default: 'DIE LEISTUNGEN' },
      titleTemplate: { type: 'string', default: 'Unsere {category} im Überblick.' },
      description: { type: 'string', default: 'Ein Auszug unserer Kernkompetenzen – präzise, zuverlässig und fachgerecht umgesetzt.' }
    },
    edit: function(props) {
      const { attributes, setAttributes } = props;
      const blockProps = useBlockProps();
      const previewTitle = attributes.titleTemplate.replace('{category}', 'Beispielkategorie');
      return el(Fragment, {},
        el(InspectorControls, {},
          el(PanelBody, { title: 'Überschrift Einstellungen', initialOpen: true },
            el(TextControl, { label: 'Label (kleiner Text)', value: attributes.label, onChange: (v)=>setAttributes({label:v}) }),
            el(TextControl, { label: 'Titel-Vorlage', value: attributes.titleTemplate, onChange: (v)=>setAttributes({titleTemplate:v}) }),
            el(TextareaControl, { label: 'Beschreibung', value: attributes.description, onChange: (v)=>setAttributes({description:v}), rows: 3 })
          )
        ),
        el('div', blockProps,
          el('div', { style: { textAlign:'center', padding:'30px', background:'#f8f9fa', borderRadius:'8px' }},
            el('span', { style: { display:'inline-block', background:'#b68c2f', color:'#fff', padding:'6px 20px', borderRadius:'20px', fontSize:'.85rem', fontWeight:'600', letterSpacing:'1px', textTransform:'uppercase', marginBottom:'15px' }}, attributes.label),
            el('h2', { style: { fontSize:'2rem', margin:'20px 0', color:'#1e4a6d' }}, el('span', { dangerouslySetInnerHTML: { __html: previewTitle.replace('Beispielkategorie', '<em style="color:#b68c2f; font-style:italic;">Beispielkategorie</em>') } })),
            el('p', { style: { color:'#666', fontSize:'1rem', margin:0 } }, attributes.description)
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
  window.wp.components
);

