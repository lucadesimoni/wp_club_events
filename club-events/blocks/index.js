/**
 * Club Events Gutenberg Blocks
 * Lightweight server-side-rendered blocks — no build step required.
 */
(function (blocks, element, blockEditor, components, i18n) {
  var el       = element.createElement;
  var __       = i18n.__;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody   = components.PanelBody;
  var TextControl = components.TextControl;
  var RangeControl= components.RangeControl;
  var ToggleControl= components.ToggleControl;
  var SelectControl= components.SelectControl;
  var ServerSideRender = window.wp && window.wp.serverSideRender
    ? window.wp.serverSideRender.default || window.wp.serverSideRender
    : null;

  /* ── Timeline Block ──────────────────────────────────────────────── */
  blocks.registerBlockType('club-events/timeline', {
    title:       __('Events Timeline', 'club-events'),
    description: __('Show upcoming club events in a vertical timeline.', 'club-events'),
    icon:        'list-view',
    category:    'widgets',
    attributes: {
      category:    { type: 'string',  default: '' },
      limit:       { type: 'number',  default: 20 },
      show_past:   { type: 'boolean', default: false },
      show_filter: { type: 'boolean', default: true },
    },
    edit: function (props) {
      var attrs = props.attributes;
      return el('div', null,
        el(InspectorControls, null,
          el(PanelBody, { title: __('Settings', 'club-events'), initialOpen: true },
            el(TextControl, {
              label: __('Category slug (optional)', 'club-events'),
              value: attrs.category,
              onChange: function (v) { props.setAttributes({ category: v }); },
            }),
            el(RangeControl, {
              label: __('Max events', 'club-events'),
              value: attrs.limit,
              min: 1, max: 100,
              onChange: function (v) { props.setAttributes({ limit: v }); },
            }),
            el(ToggleControl, {
              label:    __('Show past events', 'club-events'),
              checked:  attrs.show_past,
              onChange: function (v) { props.setAttributes({ show_past: v }); },
            }),
            el(ToggleControl, {
              label:    __('Show category filter', 'club-events'),
              checked:  attrs.show_filter,
              onChange: function (v) { props.setAttributes({ show_filter: v }); },
            })
          )
        ),
        ServerSideRender
          ? el(ServerSideRender, { block: 'club-events/timeline', attributes: attrs })
          : el('div', { className: 'ce-block-placeholder' },
              el('span', { className: 'dashicons dashicons-calendar-alt' }),
              el('p', null, __('Events Timeline — preview in frontend.', 'club-events'))
            )
      );
    },
    save: function () { return null; },
  });

  /* ── Overview Block ──────────────────────────────────────────────── */
  blocks.registerBlockType('club-events/overview', {
    title:       __('Events Calendar', 'club-events'),
    description: __('Show a monthly calendar grid of club events.', 'club-events'),
    icon:        'calendar-alt',
    category:    'widgets',
    attributes: {
      category:    { type: 'string',  default: '' },
      show_filter: { type: 'boolean', default: true },
    },
    edit: function (props) {
      var attrs = props.attributes;
      return el('div', null,
        el(InspectorControls, null,
          el(PanelBody, { title: __('Settings', 'club-events'), initialOpen: true },
            el(TextControl, {
              label: __('Category slug (optional)', 'club-events'),
              value: attrs.category,
              onChange: function (v) { props.setAttributes({ category: v }); },
            }),
            el(ToggleControl, {
              label:    __('Show category filter', 'club-events'),
              checked:  attrs.show_filter,
              onChange: function (v) { props.setAttributes({ show_filter: v }); },
            })
          )
        ),
        ServerSideRender
          ? el(ServerSideRender, { block: 'club-events/overview', attributes: attrs })
          : el('div', { className: 'ce-block-placeholder' },
              el('span', { className: 'dashicons dashicons-calendar-alt' }),
              el('p', null, __('Events Calendar — preview in frontend.', 'club-events'))
            )
      );
    },
    save: function () { return null; },
  });

  /* ── Subscribe Block ─────────────────────────────────────────────── */
  blocks.registerBlockType('club-events/subscribe', {
    title:       __('Events Subscribe Form', 'club-events'),
    description: __('Email subscription form for event notifications.', 'club-events'),
    icon:        'email-alt',
    category:    'widgets',
    attributes:  {},
    edit: function () {
      return ServerSideRender
        ? el(ServerSideRender, { block: 'club-events/subscribe', attributes: {} })
        : el('div', { className: 'ce-block-placeholder' },
            el('span', { className: 'dashicons dashicons-email-alt' }),
            el('p', null, __('Events Subscribe Form', 'club-events'))
          );
    },
    save: function () { return null; },
  });

}(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n));
