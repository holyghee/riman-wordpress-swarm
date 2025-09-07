/* global wp */
(function(wp){
  const { registerBlockType } = wp.blocks;
  const { __ } = wp.i18n;
  const { PanelBody, ToggleControl, RangeControl, TextControl, SelectControl } = wp.components;
  const { useState } = wp.element;
  const { InspectorControls } = wp.blockEditor || wp.editor;

  registerBlockType('riman/category-hero-slider', {
    title: __('Riman: Category Hero Slider', 'riman'),
    icon: 'images-alt2',
    category: 'riman',
    keywords: ['riman', 'hero', 'slider', 'kategorie', 'category'],
    attributes: {
      minHeight: { type: 'number', default: 560 },
      dim: { type: 'number', default: 30 },
      autoPlay: { type: 'boolean', default: true },
      interval: { type: 'number', default: 6000 },
      showDescription: { type: 'boolean', default: true },
      includeCategories: { type: 'array', default: [] },
      ctaText: { type: 'string', default: __('Mehr erfahren', 'riman') },
      animation: { type: 'string', default: 'fade' },
      transition: { type: 'number', default: 700 },
      dotsStyle: { type: 'string', default: 'pills' },
      arrowsShape: { type: 'string', default: 'round' },
      arrowsStyle: { type: 'string', default: 'light' },
      arrowsPosition: { type: 'string', default: 'inset' },
      parallax: { type: 'boolean', default: false },
      parallaxStrength: { type: 'number', default: 0.25 },
      parallaxMode: { type: 'string', default: 'transform' },
    },
    edit: (props) => {
      const { attributes, setAttributes } = props;
      const [include, setInclude] = useState(attributes.includeCategories.join(', '));
      return (
        wp.element.createElement('div', { className: 'riman-hero-slider-editor-placeholder' },
          wp.element.createElement('div', { style: { padding:'30px', border:'1px dashed #ccc', background:'#fafafa', width:'100%', textAlign:'center' } },
            wp.element.createElement('h3', null, __('Riman: Category Hero Slider', 'riman')),
            wp.element.createElement('p', null, __('Vorschau im Frontend. Hier kannst du die Einstellungen anpassen.', 'riman')),
          ),
          wp.element.createElement(InspectorControls, null,
            wp.element.createElement(PanelBody, { title: __('Darstellung', 'riman'), initialOpen: true },
              wp.element.createElement(RangeControl, {
                label: __('Mindesthöhe (px)', 'riman'),
                min: 320, max: 960,
                value: attributes.minHeight,
                onChange: (v)=> setAttributes({ minHeight: v })
              }),
              wp.element.createElement(RangeControl, {
                label: __('Dunkel-Overlay (%)', 'riman'),
                min: 0, max: 85,
                value: attributes.dim,
                onChange: (v)=> setAttributes({ dim: v })
              }),
              wp.element.createElement(SelectControl, {
                label: __('Animation', 'riman'),
                value: attributes.animation,
                options: [
                  { label: 'Fade', value: 'fade' },
                  { label: 'Slide', value: 'slide' }
                ],
                onChange: (v)=> setAttributes({ animation: v })
              }),
              wp.element.createElement(RangeControl, {
                label: __('Übergangsdauer (ms)', 'riman'),
                min: 150, max: 2000,
                value: attributes.transition,
                onChange: (v)=> setAttributes({ transition: v })
              }),
              wp.element.createElement(ToggleControl, {
                label: __('Parallax-Effekt', 'riman'),
                checked: attributes.parallax,
                onChange: (v)=> setAttributes({ parallax: v })
              }),
              wp.element.createElement(SelectControl, {
                label: __('Parallax-Modus', 'riman'),
                value: attributes.parallaxMode,
                options: [
                  { label: 'Transform (sanft)', value: 'transform' },
                  { label: 'Scroll (deutlich)', value: 'scroll' },
                  { label: 'Fixed (klassisch)', value: 'fixed' }
                ],
                onChange: (v)=> setAttributes({ parallaxMode: v })
              }),
              wp.element.createElement(RangeControl, {
                label: __('Parallax-Stärke', 'riman'),
                min: 0, max: 1, step: 0.05,
                value: attributes.parallaxStrength,
                onChange: (v)=> setAttributes({ parallaxStrength: v })
              }),
              wp.element.createElement(ToggleControl, {
                label: __('Beschreibung anzeigen', 'riman'),
                checked: attributes.showDescription,
                onChange: (v)=> setAttributes({ showDescription: v })
              }),
              wp.element.createElement(TextControl, {
                label: __('CTA-Text', 'riman'),
                value: attributes.ctaText,
                onChange: (v)=> setAttributes({ ctaText: v })
              })
            ),
            wp.element.createElement(PanelBody, { title: __('Slider', 'riman'), initialOpen: false },
              wp.element.createElement(ToggleControl, {
                label: __('Auto-Play', 'riman'),
                checked: attributes.autoPlay,
                onChange: (v)=> setAttributes({ autoPlay: v })
              }),
              wp.element.createElement(RangeControl, {
                label: __('Intervall (ms)', 'riman'),
                min: 2500, max: 15000,
                value: attributes.interval,
                onChange: (v)=> setAttributes({ interval: v })
              }),
              wp.element.createElement(SelectControl, {
                label: __('Dots-Stil', 'riman'),
                value: attributes.dotsStyle,
                options: [
                  { label: 'Pills', value: 'pills' },
                  { label: 'Dots', value: 'dots' },
                  { label: 'Squares', value: 'squares' }
                ],
                onChange: (v)=> setAttributes({ dotsStyle: v })
              }),
              wp.element.createElement(SelectControl, {
                label: __('Pfeil-Form', 'riman'),
                value: attributes.arrowsShape,
                options: [
                  { label: 'Rund', value: 'round' },
                  { label: 'Eckig', value: 'square' }
                ],
                onChange: (v)=> setAttributes({ arrowsShape: v })
              }),
              wp.element.createElement(SelectControl, {
                label: __('Pfeil-Stil', 'riman'),
                value: attributes.arrowsStyle,
                options: [
                  { label: 'Hell', value: 'light' },
                  { label: 'Dunkel', value: 'dark' },
                  { label: 'Ghost', value: 'ghost' }
                ],
                onChange: (v)=> setAttributes({ arrowsStyle: v })
              }),
              wp.element.createElement(SelectControl, {
                label: __('Pfeil-Position', 'riman'),
                value: attributes.arrowsPosition,
                options: [
                  { label: 'Innen', value: 'inset' },
                  { label: 'Außen', value: 'outside' }
                ],
                onChange: (v)=> setAttributes({ arrowsPosition: v })
              })
            ),
            wp.element.createElement(PanelBody, { title: __('Kategorien', 'riman'), initialOpen: false },
              wp.element.createElement(TextControl, {
                label: __('Top-Level Slugs (Komma getrennt, leer = alle)', 'riman'),
                value: include,
                onChange: (v)=>{ setInclude(v); setAttributes({ includeCategories: v.split(',').map(s=>s.trim()).filter(Boolean) }); }
              })
            ),
          )
        )
      );
    },
    save: () => null,
  });
})(window.wp);
