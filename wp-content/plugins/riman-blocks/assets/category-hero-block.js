(function (blocks, element, blockEditor, components, i18n) {
  const el = element.createElement;
  const { __ } = i18n;
  const { useBlockProps, InspectorControls } = blockEditor;
  const { PanelBody, RangeControl, ToggleControl } = components;

  const name = 'riman/category-hero';

  blocks.registerBlockType(name, {
    title: __('RIMAN: Category Hero', 'riman'),
    icon: 'format-image',
    category: 'riman',
    supports: { align: ['full'] },
    attributes: {
      minHeight: { type: 'number', default: 600 },
      dim: { type: 'number', default: 40 },
      showDescription: { type: 'boolean', default: true },
      useLinkedPageTitle: { type: 'boolean', default: true },
      overlapHeader: { type: 'boolean', default: false },
      titleMode: { type: 'string', default: 'category' },
      debug: { type: 'boolean', default: false },
    },
    edit: (props) => {
      const { attributes, setAttributes } = props;
      const bp = useBlockProps({ className: 'riman-hero--editor' });
      return el('div', bp, [
        el('div', { style:{ padding:'1rem', border:'1px dashed #c3c4c7', background:'#f6f7f7' } },
          el('strong', null, __('RIMAN: Category Hero (Vorschau)', 'riman')),
          el('p', { style:{ margin:0, color:'#666' } }, __('Wird auf Kategorieseiten mit verknüpftem Seitenbild/Video gerendert.', 'riman'))
        ),
        el(InspectorControls, {},
          el(PanelBody, { title: __('Layout', 'riman'), initialOpen: true },
            el(RangeControl, { label: __('Min. Höhe (px)', 'riman'), value: attributes.minHeight, onChange: (v)=>setAttributes({minHeight:v}), min:200, max:1200 }),
            el(RangeControl, { label: __('Overlay-Dimmung (%)', 'riman'), value: attributes.dim, onChange: (v)=>setAttributes({dim:v}), min:0, max:100 }),
            el(ToggleControl, { label: __('Beschreibung zeigen', 'riman'), checked: attributes.showDescription, onChange:(v)=>setAttributes({showDescription:v}) }),
            el(ToggleControl, { label: __('Header überlappen', 'riman'), checked: attributes.overlapHeader, onChange:(v)=>setAttributes({overlapHeader:v}) }),
            el(ToggleControl, { label: __('Debug', 'riman'), checked: attributes.debug, onChange:(v)=>setAttributes({debug:v}) }),
          )
        )
      ]);
    },
    save: () => null, // dynamic
  });
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n);
