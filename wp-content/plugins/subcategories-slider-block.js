/* global wp */
(function() {
  const { registerBlockType } = wp.blocks;
  const { __ } = wp.i18n;
  const { PanelBody, ToggleControl, Placeholder } = wp.components;
  const { InspectorControls } = wp.blockEditor || wp.editor;

  registerBlockType('riman/subcategories-slider', {
    title: __('RIMAN: Subcategories Slider', 'riman'),
    icon: 'images-alt2',
    category: 'riman',
    attributes: {
      autoplay: { type: 'boolean', default: true },
      interval: { type: 'number', default: 5000 }
    },
    edit: (props) => {
      const { attributes, setAttributes } = props;
      return (
        wp.element.createElement('div', {},
          wp.element.createElement(InspectorControls, {},
            wp.element.createElement(PanelBody, { title: __('Slider Einstellungen', 'riman'), initialOpen: true },
              wp.element.createElement(ToggleControl, {
                label: __('Autoplay', 'riman'),
                checked: !!attributes.autoplay,
                onChange: (val) => setAttributes({ autoplay: !!val })
              })
            )
          ),
          wp.element.createElement(Placeholder, {
            label: __('RIMAN: Subcategories Slider', 'riman'),
            instructions: __('Zeigt auf Kategorieseiten die Unterkategorien als Slider an (mit Bild, Titel, Beschreibung, CTA).', 'riman')
          })
        )
      );
    },
    save: () => null // Dynamic block (PHP render)
  });
})();
