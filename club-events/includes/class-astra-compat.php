<?php
defined( 'ABSPATH' ) || exit;

/**
 * Astra (Free + Premium) theme compatibility layer.
 *
 * Bridges every Astra design token into --ce-* custom properties so the
 * plugin automatically inherits the active palette, typography, button
 * styles, input styles, spacing and border-radius — zero manual config.
 */
class CE_Astra_Compat {

    private static ?bool $is_astra = null;

    public static function is_active(): bool {
        if ( null === self::$is_astra ) {
            $theme          = wp_get_theme();
            $text_domain    = strtolower( (string) $theme->get( 'TextDomain' ) );
            $parent_domain  = strtolower( (string) $theme->get( 'Template' ) );
            self::$is_astra = in_array( 'astra', [ $text_domain, $parent_domain ], true );
        }
        return self::$is_astra;
    }

    public function __construct() {
        if ( ! self::is_active() ) {
            return;
        }

        add_action( 'wp_head',            [ $this, 'bridge_css_vars' ],         1  );
        add_action( 'wp_head',            [ $this, 'output_event_schema' ],      20 );
        add_filter( 'body_class',         [ $this, 'body_classes' ]                );
        add_filter( 'astra_page_layout',  [ $this, 'single_event_layout' ]         );
        add_filter( 'astra_content_width',[ $this, 'archive_content_width' ]       );

        add_filter( 'astra_banner_visibility',      [ $this, 'hide_title_bar_on_single' ] );
        add_filter( 'astra_the_title_enabled',      [ $this, 'hide_title_bar_on_single' ] );
        add_filter( 'astra_title_bar_enabled',      [ $this, 'hide_title_bar_on_single' ] );
        add_filter( 'astra_addon_banner_visibility',[ $this, 'hide_title_bar_on_single' ] );

        add_filter( 'astra_breadcrumb_trail_items', [ $this, 'event_breadcrumbs' ], 10, 2 );

        add_filter( 'astra_metabox_page_types', [ $this, 'register_for_metabox' ] );

        add_action( 'astra_single_post_before_content', [ $this, 'maybe_suppress_entry_header' ] );
    }

    // ─── CSS Variable Bridge ──────────────────────────────────────────────

    public function bridge_css_vars(): void {
        ?>
        <style id="ce-astra-bridge">
        /* ═══════════════════════════════════════════════════════════════════
         *  Astra → Club Events design-token bridge
         *  Maps every relevant Astra CSS variable into --ce-* equivalents.
         *  When Astra vars are absent, fallbacks keep the plugin usable.
         * ═══════════════════════════════════════════════════════════════════ */
        :root {
            /* ── Colour palette ──────────────────────────────────────────── */
            --ce-primary:      var(--ast-global-color-0, #3b82f6);
            --ce-primary-dk:   var(--ast-global-color-1, #1d4ed8);
            --ce-primary-lt:   color-mix(in srgb, var(--ast-global-color-0, #3b82f6) 10%, #fff);
            --ce-accent:       var(--ast-global-color-2, var(--ce-primary));
            --ce-text:         var(--ast-global-color-3, #1e293b);
            --ce-text-muted:   var(--ast-global-color-5, #64748b);
            --ce-heading-color:var(--ast-global-color-2, var(--ce-text));
            --ce-border:       var(--ast-border-color, #e2e8f0);
            --ce-bg:           var(--ast-global-color-7, #f8fafc);
            --ce-white:        var(--ast-global-color-6, #ffffff);
            --ce-link:         var(--ast-global-color-0, var(--ce-primary));
            --ce-link-hover:   var(--ast-global-color-1, var(--ce-primary-dk));

            /* ── Typography ──────────────────────────────────────────────── */
            --ce-font-family:         var(--ast-body-font-family, inherit);
            --ce-heading-font-family: var(--ast-heading-font-family, var(--ce-font-family));
            --ce-body-font-size:      var(--ast-body-font-size, 16px);
            --ce-body-line-height:    var(--ast-body-line-height, 1.65);
            --ce-body-font-weight:    var(--ast-body-font-weight, 400);
            --ce-heading-font-weight: var(--ast-heading-font-weight, 700);
            --ce-heading-line-height: var(--ast-heading-line-height, 1.3);

            /* ── Heading sizes ───────────────────────────────────────────── */
            --ce-h1-size: var(--ast-heading-font-size-h1, 2rem);
            --ce-h2-size: var(--ast-heading-font-size-h2, 1.6rem);
            --ce-h3-size: var(--ast-heading-font-size-h3, 1.2rem);
            --ce-h4-size: var(--ast-heading-font-size-h4, 1rem);
            --ce-h5-size: var(--ast-heading-font-size-h5, .875rem);

            /* ── Buttons ─────────────────────────────────────────────────── */
            --ce-btn-bg:          var(--ast-global-color-0, var(--ce-primary));
            --ce-btn-bg-hover:    var(--ast-global-color-1, var(--ce-primary-dk));
            --ce-btn-color:       var(--ast-global-color-6, #fff);
            --ce-btn-color-hover: var(--ast-global-color-6, #fff);
            --ce-btn-radius:      var(--ast-button-border-radius, 6px);
            --ce-btn-font-size:   var(--ast-button-font-size, 14px);
            --ce-btn-font-weight: var(--ast-button-font-weight, 600);
            --ce-btn-text-transform: var(--ast-button-text-transform, none);
            --ce-btn-letter-spacing: var(--ast-button-letter-spacing, normal);
            --ce-btn-padding-h:   var(--ast-button-h-padding, 18px);
            --ce-btn-padding-v:   var(--ast-button-v-padding, 8px);

            /* ── Inputs ──────────────────────────────────────────────────── */
            --ce-input-border:    var(--ast-border-color, #e2e8f0);
            --ce-input-focus:     var(--ast-global-color-0, var(--ce-primary));
            --ce-input-radius:    var(--ast-button-border-radius, 4px);
            --ce-input-bg:        var(--ast-global-color-6, #fff);
            --ce-input-color:     var(--ast-global-color-3, var(--ce-text));

            /* ── Spacing & Layout ────────────────────────────────────────── */
            --ce-content-padding: var(--ast-content-spacing, 20px);
            --ce-section-spacing: var(--ast-section-spacing, 2rem);
            --ce-radius:          var(--ast-button-border-radius, 10px);
            --ce-radius-sm:       max(2px, calc(var(--ast-button-border-radius, 6px) - 2px));
        }

        /* ── Apply inherited fonts ────────────────────────────────────── */
        .ce-timeline-wrap, .ce-overview-wrap, .ce-cards-wrap,
        .ce-yearly-wrap, .ce-submit-wrap, .ce-my-events,
        .ce-event-hero, .ce-event-body-wrap, .ce-archive-wrap,
        .ce-subscribe-wrap, .ce-event-list {
            font-family: var(--ce-font-family);
            font-size: var(--ce-body-font-size);
            line-height: var(--ce-body-line-height);
            color: var(--ce-text);
        }
        .ce-event-title, .ce-card-title, .ce-month-label,
        .ce-yearly-month-title, .ce-archive-title, .ce-sidebar-card h3,
        .ce-subscribe-title, .ce-cal-title, .ce-overview-list-title {
            font-family: var(--ce-heading-font-family);
            font-weight: var(--ce-heading-font-weight);
            line-height: var(--ce-heading-line-height);
            color: var(--ce-heading-color);
        }

        /* ── Heading sizes ────────────────────────────────────────────── */
        .ce-event-hero .ce-event-title { font-size: var(--ce-h1-size); color: #fff !important; }
        .ce-archive-title              { font-size: var(--ce-h1-size); }
        .ce-subscribe-title            { font-size: var(--ce-h3-size); }
        .ce-overview-list-title        { font-size: var(--ce-h4-size); }
        .ce-card-title                 { font-size: var(--ce-h5-size); }
        .ce-month-label,
        .ce-yearly-month-title         { font-size: var(--ce-h5-size); }

        /* ── Links ────────────────────────────────────────────────────── */
        .ce-event-title a,
        .ce-card-title,
        .ce-tile-card-title,
        .ce-list-title,
        .ce-upcoming-title,
        .ce-yearly-event-title { color: var(--ce-text); }
        .ce-event-title a:hover,
        .ce-card-item:hover .ce-card-title,
        .ce-tile-card:hover .ce-tile-card-title,
        .ce-list-title:hover,
        .ce-upcoming-title:hover { color: var(--ce-link); }
        .ce-tile-card-title { border-bottom-color: var(--ce-primary); }
        .ce-tile-card-cta { color: var(--ce-link); }
        .ce-tile-card-date { color: var(--ce-text-muted); }
        .ce-tile-card-excerpt { color: var(--ce-text); }
        .ce-card-cta,
        .ce-card-link,
        .ce-back-link:hover { color: var(--ce-link); }

        /* ── Buttons ──────────────────────────────────────────────────── */
        .ce-btn {
            font-family: var(--ce-font-family);
            font-size: var(--ce-btn-font-size);
            font-weight: var(--ce-btn-font-weight);
            text-transform: var(--ce-btn-text-transform);
            letter-spacing: var(--ce-btn-letter-spacing);
            border-radius: var(--ce-btn-radius);
            padding: var(--ce-btn-padding-v) var(--ce-btn-padding-h);
        }
        .ce-btn-primary {
            background: var(--ce-btn-bg);
            border-color: var(--ce-btn-bg);
            color: var(--ce-btn-color) !important;
        }
        .ce-btn-primary:hover {
            background: var(--ce-btn-bg-hover);
            border-color: var(--ce-btn-bg-hover);
            color: var(--ce-btn-color-hover) !important;
        }
        .ce-btn-outline {
            border-color: var(--ce-border);
            color: var(--ce-text-muted) !important;
        }
        .ce-btn-outline:hover {
            border-color: var(--ce-link);
            color: var(--ce-link) !important;
        }

        /* ── Filter bar — match Astra button feel ─────────────────────── */
        .ce-filter-btn {
            font-family: var(--ce-font-family);
            border-radius: var(--ce-btn-radius);
            font-size: var(--ce-btn-font-size);
            font-weight: var(--ce-btn-font-weight);
            letter-spacing: var(--ce-btn-letter-spacing);
        }
        .ce-filter-btn:hover,
        .ce-filter-btn.active {
            background: var(--ce-btn-bg);
            border-color: var(--ce-btn-bg);
            color: var(--ce-btn-color);
        }

        /* ── Form inputs — match Astra form styling ───────────────────── */
        .ce-subscribe-form input[type="text"],
        .ce-subscribe-form input[type="email"],
        .ce-submit-form input[type="text"],
        .ce-submit-form input[type="datetime-local"],
        .ce-submit-form input[type="date"],
        .ce-submit-form textarea,
        .ce-submit-form select {
            font-family: var(--ce-font-family);
            font-size: var(--ce-body-font-size);
            color: var(--ce-input-color);
            background: var(--ce-input-bg);
            border-color: var(--ce-input-border);
            border-radius: var(--ce-input-radius);
        }
        .ce-subscribe-form input:focus,
        .ce-submit-form input:focus,
        .ce-submit-form textarea:focus,
        .ce-submit-form select:focus {
            border-color: var(--ce-input-focus);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--ce-input-focus) 15%, transparent);
        }

        /* ── Cards — inherit Astra surface tokens ─────────────────────── */
        .ce-card-item,
        .ce-tile-card,
        .ce-timeline-body,
        .ce-sidebar-card,
        .ce-subscribe-wrap,
        .ce-submit-wrap {
            background: var(--ce-white);
            border-color: var(--ce-border);
            border-radius: var(--ce-radius);
        }

        /* ── Calendar grid ────────────────────────────────────────────── */
        .ce-calendar-grid {
            border-color: var(--ce-border);
            border-radius: var(--ce-radius);
        }
        .ce-cal-header { background: var(--ce-bg); color: var(--ce-text-muted); }
        .ce-cal-day    { background: var(--ce-white); }
        .ce-cal-empty  { background: var(--ce-bg); }
        .ce-cal-today .ce-cal-day-num { background: var(--ce-primary); color: var(--ce-btn-color); }

        /* ── Category / type badges ───────────────────────────────────── */
        .ce-category-badge {
            background: var(--ce-primary-lt);
            color: var(--ce-primary);
            border-radius: var(--ce-btn-radius);
        }
        .ce-category-badge:hover {
            background: var(--ce-primary);
            color: var(--ce-btn-color);
        }

        /* ── Yearly agenda ────────────────────────────────────────────── */
        .ce-yearly-event:hover   { background: var(--ce-bg); }
        .ce-yearly-month-title   { border-bottom-color: var(--ce-border); }
        .ce-yearly-event-date    { color: var(--ce-text-muted); }
        .ce-yearly-event-time,
        .ce-yearly-event-loc     { color: var(--ce-text-muted); }

        /* ── Subscribe form ───────────────────────────────────────────── */
        .ce-subscribe-wrap {
            background: var(--ce-white);
            border-color: var(--ce-border);
        }
        .ce-subscribe-title { color: var(--ce-heading-color); }
        .ce-subscribe-desc  { color: var(--ce-text-muted); }

        /* ── Single event hero ────────────────────────────────────────── */
        .ce-event-hero {
            border-radius: var(--ce-radius);
        }
        .ce-meta-pill {
            font-family: var(--ce-font-family);
        }

        /* ── Full-bleed hero inside Astra content column ──────────────── */
        .ce-single-event .ce-event-hero {
            width:       100vw;
            margin-left: calc(50% - 50vw);
            margin-right:calc(50% - 50vw);
            border-radius: 0;
        }
        @supports (scrollbar-gutter: stable) {
            .ce-single-event .ce-event-hero {
                width:        calc(100vw - var(--ast-scrollbar-width, 0px));
                margin-left:  calc(50% - 50vw + var(--ast-scrollbar-width,0px) / 2);
                margin-right: calc(50% - 50vw + var(--ast-scrollbar-width,0px) / 2);
            }
        }

        /* ── Astra layout ─────────────────────────────────────────────── */
        body.single-club_event #primary.content-area {
            width: 100%;
            max-width: 100%;
            float: none;
        }
        body.single-club_event #secondary { display: none; }

        body.post-type-archive-club_event .ast-container > #primary,
        body.tax-event_category .ast-container > #primary,
        body.tax-event_tag      .ast-container > #primary {
            flex: 1;
            min-width: 0;
        }

        .ast-separate-container .ce-event-hero { border-radius: 0; }

        /* ── Z-index safety ───────────────────────────────────────────── */
        #masthead, .main-header-bar, .ast-primary-sticky-header { z-index: 1000 !important; }
        .ce-filter-bar, .ce-cal-nav { z-index: 10; }

        /* ── Hide Astra entry-title on single events ──────────────────── */
        body.single-club_event .ast-separate-container .ast-article-single .entry-header,
        body.single-club_event .ast-plain-container .ast-article-single .entry-header,
        body.single-club_event .entry-header .entry-title { display: none; }

        /* ── Content spacing ──────────────────────────────────────────── */
        .ce-archive-wrap, .ce-timeline-wrap, .ce-cards-wrap,
        .ce-overview-wrap, .ce-yearly-wrap, .ce-submit-wrap,
        .ce-my-events, .ce-event-list {
            padding-top: var(--ce-section-spacing);
        }

        /* ── ICS link, view buttons — match Astra feel ────────────────── */
        .ce-ics-link,
        .ce-cal-nav-btn,
        .ce-view-btn {
            border-color: var(--ce-border);
            color: var(--ce-text-muted);
            border-radius: var(--ce-btn-radius);
        }
        .ce-ics-link:hover,
        .ce-cal-nav-btn:hover,
        .ce-view-btn:hover {
            border-color: var(--ce-link);
            color: var(--ce-link);
        }

        /* ── Event list items ─────────────────────────────────────────── */
        .ce-list-item,
        .ce-event-list-item { border-color: var(--ce-border); }
        .ce-event-list-item a { color: var(--ce-text); }
        .ce-event-list-item a:hover { color: var(--ce-link); }

        /* ── Sidebar ──────────────────────────────────────────────────── */
        .ce-sidebar-card {
            background: var(--ce-white);
            border-color: var(--ce-border);
        }
        .ce-sidebar-card h3 { color: var(--ce-text-muted); border-color: var(--ce-border); }
        .ce-detail-icon { background: var(--ce-primary-lt); color: var(--ce-primary); }
        .ce-detail-row strong { color: var(--ce-text-muted); }
        .ce-detail-row p { color: var(--ce-text); }

        /* ── My Events table ──────────────────────────────────────────── */
        .ce-my-events-table {
            font-family: var(--ce-font-family);
        }
        .ce-my-events-table th { color: var(--ce-text-muted); border-color: var(--ce-border); }
        .ce-my-events-table td { border-color: var(--ce-border); }
        .ce-my-events-table strong a { color: var(--ce-text); }
        .ce-my-events-table strong a:hover { color: var(--ce-link); }

        /* ── Astra Pro transparent header ──────────────────────────────── */
        body.single-club_event.ast-transparent-header .ce-event-hero {
            padding-top: calc(var(--ast-transparent-header-logo-width, 80px) + 40px);
        }
        .ast-header-above-grid-enabled #content,
        .ast-header-below-grid-enabled #content {
            position: relative;
            z-index: 1;
        }

        /* ── Astra separate-container spacing ─────────────────────────── */
        .ast-separate-container .ce-event-body-wrap,
        .ast-separate-container .ce-archive-wrap {
            padding-left: 0;
            padding-right: 0;
        }

        /* ── Page content bottom spacing ──────────────────────────────── */
        .ce-archive-wrap,
        .site-main > .ce-single-event {
            padding-bottom: var(--ce-section-spacing);
        }

        /* ── Breadcrumb ───────────────────────────────────────────────── */
        .ce-breadcrumb-wrap { padding: 10px 0 0; font-size: 13px; }
        .ce-breadcrumb-wrap .astra-breadcrumbs { padding: 0; background: none; }

        /* ── Astra woo accent ─────────────────────────────────────────── */
        .ast-woocommerce-container .ce-btn-primary { background: var(--ce-btn-bg); }

        /* ── Admin bar ────────────────────────────────────────────────── */
        .admin-bar .ce-event-hero { margin-top: 0; }

        /* ── Card date badge — match Astra surface ────────────────────── */
        .ce-card-date-badge {
            background: var(--ce-white);
            border-color: var(--ce-border);
            border-radius: var(--ce-radius-sm);
        }

        /* ── Timeline dot / line — use palette ────────────────────────── */
        .ce-timeline-item::before { background: var(--ce-border); }
        .ce-timeline-body { border-color: var(--ce-border); }

        /* ── Archive toolbar ──────────────────────────────────────────── */
        .ce-view-switcher {
            background: var(--ce-bg);
            border-color: var(--ce-border);
        }
        .ce-view-btn.active, .ce-view-btn:hover {
            background: var(--ce-white);
            color: var(--ce-primary);
        }

        /* ── Card ICS icon ────────────────────────────────────────────── */
        .ce-card-ics {
            border-color: var(--ce-border);
            color: var(--ce-text-muted);
        }
        .ce-card-ics:hover {
            border-color: var(--ce-link);
            color: var(--ce-link);
            background: var(--ce-primary-lt);
        }

        /* ── Card footer border ───────────────────────────────────────── */
        .ce-card-footer { border-color: var(--ce-border); }

        /* ── Meta colours ─────────────────────────────────────────────── */
        .ce-meta-item, .ce-card-meta-row,
        .ce-list-location, .ce-event-list-date,
        .ce-event-list-loc { color: var(--ce-text-muted); }
        </style>
        <?php
    }

    // ─── JSON-LD Event Schema ─────────────────────────────────────────────

    public function output_event_schema(): void {
        if ( ! is_singular( 'club_event' ) ) {
            return;
        }

        $post_id  = get_the_ID();
        $start    = get_post_meta( $post_id, '_ce_start_date', true );
        $end      = get_post_meta( $post_id, '_ce_end_date', true );
        $location = get_post_meta( $post_id, '_ce_location', true );
        $loc_url  = get_post_meta( $post_id, '_ce_location_url', true );
        $ext_url  = get_post_meta( $post_id, '_ce_external_url', true );

        if ( ! $start ) {
            return;
        }

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Event',
            'name'        => get_the_title( $post_id ),
            'description' => wp_strip_all_tags( get_post_field( 'post_excerpt', $post_id ) ?: get_post_field( 'post_content', $post_id ) ),
            'startDate'   => date( 'c', strtotime( $start ) ),
            'url'         => get_permalink( $post_id ),
            'organizer'   => [
                '@type' => 'Organization',
                'name'  => get_bloginfo( 'name' ),
                'url'   => home_url(),
            ],
        ];

        if ( $end ) {
            $schema['endDate'] = date( 'c', strtotime( $end ) );
        }

        if ( $location ) {
            $schema['location'] = [
                '@type' => 'Place',
                'name'  => $location,
            ];
            if ( $loc_url ) {
                $schema['location']['url'] = $loc_url;
            }
        }

        if ( $ext_url ) {
            $schema['url'] = $ext_url;
        }

        if ( has_post_thumbnail( $post_id ) ) {
            $schema['image'] = get_the_post_thumbnail_url( $post_id, 'large' );
        }

        $schema['eventStatus']        = 'https://schema.org/EventScheduled';
        $schema['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';

        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }

    // ─── Body Classes ─────────────────────────────────────────────────────

    public function body_classes( array $classes ): array {
        if ( is_singular( 'club_event' ) ) {
            $classes[] = 'ce-single-event-page';
            $classes[] = 'ast-no-sidebar';
        }
        if ( is_post_type_archive( 'club_event' ) || is_tax( [ 'event_category', 'event_type', 'event_tag' ] ) ) {
            $classes[] = 'ce-archive-page';
        }
        return $classes;
    }

    // ─── Layout ───────────────────────────────────────────────────────────

    public function single_event_layout( string $layout ): string {
        if ( is_singular( 'club_event' ) ) {
            return 'no-sidebar';
        }
        return $layout;
    }

    public function archive_content_width( string $width ): string {
        if ( is_post_type_archive( 'club_event' ) || is_tax( [ 'event_category', 'event_type', 'event_tag' ] ) ) {
            return '100';
        }
        return $width;
    }

    // ─── Title Bar ────────────────────────────────────────────────────────

    public function hide_title_bar_on_single( $value ) {
        if ( is_singular( 'club_event' ) ) {
            return false;
        }
        return $value;
    }

    public function maybe_suppress_entry_header(): void {
        if ( ! is_singular( 'club_event' ) ) {
            return;
        }
        remove_action( 'astra_single_post_before_content', 'astra_entry_header_template' );
    }

    // ─── Breadcrumbs ─────────────────────────────────────────────────────

    public function event_breadcrumbs( array $items, array $args ): array {
        if ( ! is_singular( 'club_event' ) && ! is_post_type_archive( 'club_event' ) ) {
            return $items;
        }

        $archive_url   = get_post_type_archive_link( 'club_event' );
        $archive_label = __( 'Events', 'club-events' );

        $trail = [
            '<a href="' . esc_url( home_url() ) . '">' . __( 'Home', 'club-events' ) . '</a>',
            '<a href="' . esc_url( $archive_url ) . '">' . esc_html( $archive_label ) . '</a>',
        ];

        if ( is_singular( 'club_event' ) ) {
            $cats = wp_get_post_terms( get_the_ID(), 'event_category' );
            if ( ! is_wp_error( $cats ) && $cats ) {
                $trail[] = '<a href="' . esc_url( get_term_link( $cats[0] ) ) . '">' . esc_html( $cats[0]->name ) . '</a>';
            }
            $trail[] = get_the_title();
        } elseif ( is_tax() ) {
            $trail[] = single_term_title( '', false );
        }

        return $trail;
    }

    // ─── Admin ────────────────────────────────────────────────────────────

    public function register_for_metabox( array $types ): array {
        $types[] = 'club_event';
        return $types;
    }
}
