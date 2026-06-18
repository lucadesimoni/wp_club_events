/**
 * Club Events Gutenberg Blocks
 * Lightweight server-side-rendered blocks — no build step required.
 */
(function (blocks, element, blockEditor, components, i18n) {
  var el       = element.createElement;
  var __       = i18n.__;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody    = components.PanelBody;
  var TextControl  = components.TextControl;
  var RangeControl = components.RangeControl;
  var ToggleControl= components.ToggleControl;
  var SelectControl= components.SelectControl;
  var ServerSideRender = window.wp && window.wp.serverSideRender
    ? window.wp.serverSideRender.default || window.wp.serverSideRender
    : null;

  var CATEGORY = 'club-events';

  function filterControls(props) {
    var attrs = props.attributes;
    return [
      el(TextControl, {
        label: __('Category slug', 'club-events'),
        help:  __('Filter by event category. Leave empty for all.', 'club-events'),
        value: attrs.category,
        onChange: function (v) { props.setAttributes({ category: v }); },
      }),
      el(TextControl, {
        label: __('Event type slug', 'club-events'),
        help:  __('Filter by event type. Leave empty for all.', 'club-events'),
        value: attrs.event_type,
        onChange: function (v) { props.setAttributes({ event_type: v }); },
      }),
      el(SelectControl, {
        label: __('Filter bar shows', 'club-events'),
        value: attrs.filter_by,
        options: [
          { label: __('Categories', 'club-events'), value: 'category' },
          { label: __('Event Types', 'club-events'), value: 'event_type' },
        ],
        onChange: function (v) { props.setAttributes({ filter_by: v }); },
      }),
    ];
  }

  function placeholder(icon, label) {
    return el('div', { className: 'ce-block-placeholder' },
      el('span', { className: 'dashicons dashicons-' + icon }),
      el('p', null, label)
    );
  }

  /* ── Timeline Block ──────────────────────────────────────────────── */
  blocks.registerBlockType('club-events/timeline', {
    title:       __('Events Timeline', 'club-events'),
    description: __('Show upcoming club events in a vertical timeline.', 'club-events'),
    icon:        'list-view',
    category:    CATEGORY,
    supports:    { align: ['wide', 'full'] },
    attributes: {
      category:    { type: 'string',  default: '' },
      event_type:  { type: 'string',  default: '' },
      filter_by:   { type: 'string',  default: 'category' },
      limit:       { type: 'number',  default: 20 },
      show_past:   { type: 'boolean', default: false },
      show_filter: { type: 'boolean', default: true },
    },
    edit: function (props) {
      var attrs = props.attributes;
      return el('div', null,
        el(InspectorControls, null,
          el(PanelBody, { title: __('Query', 'club-events'), initialOpen: true },
            filterControls(props),
            el(RangeControl, {
              label: __('Max events', 'club-events'),
              value: attrs.limit, min: 1, max: 100,
              onChange: function (v) { props.setAttributes({ limit: v }); },
            }),
            el(ToggleControl, {
              label: __('Show past events', 'club-events'),
              checked: attrs.show_past,
              onChange: function (v) { props.setAttributes({ show_past: v }); },
            })
          ),
          el(PanelBody, { title: __('Display', 'club-events'), initialOpen: false },
            el(ToggleControl, {
              label: __('Show filter bar', 'club-events'),
              checked: attrs.show_filter,
              onChange: function (v) { props.setAttributes({ show_filter: v }); },
            })
          )
        ),
        ServerSideRender
          ? el(ServerSideRender, { block: 'club-events/timeline', attributes: attrs })
          : placeholder('list-view', __('Events Timeline — preview in frontend.', 'club-events'))
      );
    },
    save: function () { return null; },
  });

  /* ── Overview Block ──────────────────────────────────────────────── */
  blocks.registerBlockType('club-events/overview', {
    title:       __('Events Calendar', 'club-events'),
    description: __('Show a monthly calendar grid of club events.', 'club-events'),
    icon:        'calendar-alt',
    category:    CATEGORY,
    supports:    { align: ['wide', 'full'] },
    attributes: {
      category:    { type: 'string',  default: '' },
      event_type:  { type: 'string',  default: '' },
      filter_by:   { type: 'string',  default: 'category' },
      show_filter: { type: 'boolean', default: true },
    },
    edit: function (props) {
      var attrs = props.attributes;
      return el('div', null,
        el(InspectorControls, null,
          el(PanelBody, { title: __('Query', 'club-events'), initialOpen: true },
            filterControls(props)
          ),
          el(PanelBody, { title: __('Display', 'club-events'), initialOpen: false },
            el(ToggleControl, {
              label: __('Show filter bar', 'club-events'),
              checked: attrs.show_filter,
              onChange: function (v) { props.setAttributes({ show_filter: v }); },
            })
          )
        ),
        ServerSideRender
          ? el(ServerSideRender, { block: 'club-events/overview', attributes: attrs })
          : placeholder('calendar-alt', __('Events Calendar — preview in frontend.', 'club-events'))
      );
    },
    save: function () { return null; },
  });

  /* ── Cards Block ─────────────────────────────────────────────────── */
  blocks.registerBlockType('club-events/cards', {
    title:       __('Events Cards', 'club-events'),
    description: __('Show club events in a responsive card grid.', 'club-events'),
    icon:        'grid-view',
    category:    CATEGORY,
    supports:    { align: ['wide', 'full'] },
    attributes: {
      category:    { type: 'string',  default: '' },
      event_type:  { type: 'string',  default: '' },
      filter_by:   { type: 'string',  default: 'category' },
      limit:       { type: 'number',  default: 6 },
      columns:     { type: 'number',  default: 3 },
      show_past:   { type: 'boolean', default: false },
      show_filter: { type: 'boolean', default: true },
      show_image:  { type: 'boolean', default: true },
    },
    edit: function (props) {
      var attrs = props.attributes;
      return el('div', null,
        el(InspectorControls, null,
          el(PanelBody, { title: __('Query', 'club-events'), initialOpen: true },
            filterControls(props),
            el(RangeControl, { label: __('Max events', 'club-events'), value: attrs.limit, min: 1, max: 50, onChange: function(v){ props.setAttributes({limit:v}); } }),
            el(ToggleControl, { label: __('Show past events', 'club-events'), checked: attrs.show_past, onChange: function(v){ props.setAttributes({show_past:v}); } })
          ),
          el(PanelBody, { title: __('Display', 'club-events'), initialOpen: false },
            el(RangeControl, { label: __('Columns', 'club-events'), value: attrs.columns, min: 1, max: 4, onChange: function(v){ props.setAttributes({columns:v}); } }),
            el(ToggleControl, { label: __('Show image', 'club-events'), checked: attrs.show_image, onChange: function(v){ props.setAttributes({show_image:v}); } }),
            el(ToggleControl, { label: __('Show filter bar', 'club-events'), checked: attrs.show_filter, onChange: function(v){ props.setAttributes({show_filter:v}); } })
          )
        ),
        ServerSideRender
          ? el(ServerSideRender, { block: 'club-events/cards', attributes: attrs })
          : placeholder('grid-view', __('Events Cards', 'club-events'))
      );
    },
    save: function () { return null; },
  });

  /* ── List Block ──────────────────────────────────────────────────── */
  blocks.registerBlockType('club-events/list', {
    title:       __('Events List', 'club-events'),
    description: __('Show upcoming events in a compact list.', 'club-events'),
    icon:        'editor-ul',
    category:    CATEGORY,
    supports:    { align: ['wide', 'full'] },
    attributes: {
      category:    { type: 'string',  default: '' },
      event_type:  { type: 'string',  default: '' },
      limit:       { type: 'number',  default: 5 },
      show_past:   { type: 'boolean', default: false },
    },
    edit: function (props) {
      var attrs = props.attributes;
      return el('div', null,
        el(InspectorControls, null,
          el(PanelBody, { title: __('Query', 'club-events'), initialOpen: true },
            el(TextControl, {
              label: __('Category slug', 'club-events'),
              value: attrs.category,
              onChange: function (v) { props.setAttributes({ category: v }); },
            }),
            el(TextControl, {
              label: __('Event type slug', 'club-events'),
              value: attrs.event_type,
              onChange: function (v) { props.setAttributes({ event_type: v }); },
            }),
            el(RangeControl, {
              label: __('Max events', 'club-events'),
              value: attrs.limit, min: 1, max: 50,
              onChange: function (v) { props.setAttributes({ limit: v }); },
            }),
            el(ToggleControl, {
              label: __('Show past events', 'club-events'),
              checked: attrs.show_past,
              onChange: function (v) { props.setAttributes({ show_past: v }); },
            })
          )
        ),
        ServerSideRender
          ? el(ServerSideRender, { block: 'club-events/list', attributes: attrs })
          : placeholder('editor-ul', __('Events List — preview in frontend.', 'club-events'))
      );
    },
    save: function () { return null; },
  });

  /* ── Subscribe Block ─────────────────────────────────────────────── */
  blocks.registerBlockType('club-events/subscribe', {
    title:       __('Events Subscribe Form', 'club-events'),
    description: __('Email subscription form for event notifications.', 'club-events'),
    icon:        'email-alt',
    category:    CATEGORY,
    attributes:  {},
    edit: function () {
      return ServerSideRender
        ? el(ServerSideRender, { block: 'club-events/subscribe', attributes: {} })
        : placeholder('email-alt', __('Events Subscribe Form', 'club-events'));
    },
    save: function () { return null; },
  });

}(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n));
