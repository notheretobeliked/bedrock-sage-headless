/**
 * @see {@link https://bud.js.org/extensions/bud-preset-wordpress/editor-integration/filters}
 */
roots.register.filters('@scripts/filters');

/**
 * Extend core/group with section behaviour, reveal and parallax attributes.
 *
 * The schema is registered here so the attributes serialise into the saved
 * markup (the matching PHP defaults live in app/blocks.php). The inspector
 * UI below applies to every core/group.
 */
wp.hooks.addFilter(
  'blocks.registerBlockType',
  'sage/group-section-attributes',
  (settings, name) => {
    if (name !== 'core/group') return settings;

    return {
      ...settings,
      attributes: {
        ...settings.attributes,
        sectionBehavior: { type: 'string', default: 'normal' },
        sectionMinHeight: { type: 'string', default: 'auto' },
        sectionContentAlign: { type: 'string', default: 'center' },
        sectionReveal: { type: 'string', default: 'none' },
        sectionRevealDirection: { type: 'string', default: 'up' },
        sectionRevealStagger: { type: 'number', default: 60 },
        sectionParallax: { type: 'boolean', default: false },
      },
    };
  },
);

/**
 * Add the Section behaviour / Reveal animation / Parallax inspector panels
 * to every core/group.
 */
const groupSectionInspector = wp.compose.createHigherOrderComponent(
  (BlockEdit) => (props) => {
    const el = wp.element.createElement;
    const { Fragment } = wp.element;

    if (props.name !== 'core/group') {
      return el(BlockEdit, props);
    }

    const { attributes, setAttributes } = props;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl, RangeControl, ToggleControl } = wp.components;

    return el(
      Fragment,
      null,
      el(BlockEdit, props),
      el(
        InspectorControls,
        null,
        el(
          PanelBody,
          { title: 'Section behaviour', initialOpen: false },
          el(SelectControl, {
            label: 'Scroll behaviour',
            help: 'Stick: pins to viewport; subsequent sections slide over it.',
            value: attributes.sectionBehavior || 'normal',
            options: [
              { label: 'Normal', value: 'normal' },
              { label: 'Sticky-stack', value: 'stick' },
            ],
            onChange: (value) => setAttributes({ sectionBehavior: value }),
          }),
          el(SelectControl, {
            label: 'Minimum height',
            value: attributes.sectionMinHeight || 'auto',
            options: [
              { label: 'Auto (content height)', value: 'auto' },
              { label: 'Full screen (100vh)', value: 'screen' },
              { label: 'Half screen (50vh)', value: 'half' },
            ],
            onChange: (value) => setAttributes({ sectionMinHeight: value }),
          }),
          el(SelectControl, {
            label: 'Content alignment',
            help: 'How content sits inside the section. Stretch makes children fill the height (e.g. for a column with an image gallery).',
            value: attributes.sectionContentAlign || 'center',
            options: [
              { label: 'Center (default)', value: 'center' },
              { label: 'Top', value: 'top' },
              { label: 'Bottom', value: 'bottom' },
              { label: 'Stretch (fill height)', value: 'stretch' },
            ],
            onChange: (value) => setAttributes({ sectionContentAlign: value }),
          }),
        ),
        el(
          PanelBody,
          { title: 'Reveal animation', initialOpen: false },
          el(SelectControl, {
            label: 'Trigger',
            value: attributes.sectionReveal || 'none',
            options: [
              { label: 'None', value: 'none' },
              { label: 'Scroll-locked (reveals as you scroll)', value: 'scroll-locked' },
              { label: 'Once on enter (plays once in view)', value: 'once-on-enter' },
            ],
            onChange: (value) => setAttributes({ sectionReveal: value }),
          }),
          attributes.sectionReveal && attributes.sectionReveal !== 'none'
            ? el(SelectControl, {
                label: 'Direction',
                value: attributes.sectionRevealDirection || 'up',
                options: [
                  { label: 'Slide up + fade', value: 'up' },
                  { label: 'Slide from left + fade', value: 'from-left' },
                  { label: 'Fade only', value: 'fade-only' },
                ],
                onChange: (value) => setAttributes({ sectionRevealDirection: value }),
              })
            : null,
          attributes.sectionReveal && attributes.sectionReveal !== 'none'
            ? el(RangeControl, {
                label: 'Per-word delay (ms)',
                value: attributes.sectionRevealStagger ?? 60,
                onChange: (value) => setAttributes({ sectionRevealStagger: value }),
                min: 0,
                max: 300,
                step: 10,
              })
            : null,
        ),
        el(
          PanelBody,
          { title: 'Parallax', initialOpen: false },
          el(ToggleControl, {
            label: 'Enable parallax scroll',
            help: 'Each direct child of this group moves at a different scroll speed. CoreColumns is treated as transparent — each Column inside becomes one parallax unit instead. Wrap multiple blocks in a sub-group to bundle them as one unit.',
            checked: !!attributes.sectionParallax,
            onChange: (value) => setAttributes({ sectionParallax: !!value }),
          }),
        ),
      ),
    );
  },
  'groupSectionInspector',
);

wp.hooks.addFilter(
  'editor.BlockEdit',
  'sage/group-section-inspector',
  groupSectionInspector,
);

/**
 * Reflect min-height / sticky / content alignment on the editor block wrapper
 * while authoring. Pure styling (see resources/styles/editor.css); does not
 * affect saved markup.
 */
const groupSectionEditorClasses = wp.compose.createHigherOrderComponent(
  (BlockListBlock) => (props) => {
    const el = wp.element.createElement;
    if (props.name !== 'core/group') return el(BlockListBlock, props);

    const { attributes } = props;
    const extra = [];
    if (attributes.sectionMinHeight === 'screen') extra.push('section-editor-min-screen');
    else if (attributes.sectionMinHeight === 'half') extra.push('section-editor-min-half');
    if (attributes.sectionBehavior === 'stick') extra.push('section-editor-sticky');
    if (attributes.sectionContentAlign && attributes.sectionContentAlign !== 'center') {
      extra.push(`section-editor-align-${attributes.sectionContentAlign}`);
    }

    if (!extra.length) return el(BlockListBlock, props);

    return el(BlockListBlock, {
      ...props,
      className: `${props.className || ''} ${extra.join(' ')}`.trim(),
    });
  },
  'groupSectionEditorClasses',
);

wp.hooks.addFilter(
  'editor.BlockListBlock',
  'sage/group-section-editor-classes',
  groupSectionEditorClasses,
);

/**
 * @see {@link https://webpack.js.org/api/hot-module-replacement/}
 */
if (import.meta.webpackHot) import.meta.webpackHot.accept(console.error);
