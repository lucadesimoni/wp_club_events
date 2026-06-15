<?php
defined( 'ABSPATH' ) || exit;

/**
 * Astra (Free + Premium) theme compatibility layer.
 *
 * Handles:
 *  - CSS variable bridging (--ast-global-color-* → --ce-*)
 *  - Template structure (Astra container / sidebar wrappers)
 *  - Page-title / banner integration (hide Astra's title on single events — we have our own hero)
 *  - Sidebar layout control
 *  - Breadcrumb integration
 *  - Event JSON-LD schema (works with Astra SEO / RankMath / Yoast)
 *  - Body classes
 *  - Admin meta-box page-type registration
 */
class CE_Astra_Compat {

    private static ?bool $is_astra = null;

    /** Returns true when Astra (any flavour) is the active theme. */
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

        // Hide Astra's built-in page-title bar on single events (our hero replaces it)
        add_filter( 'astra_banner_visibility',      [ $this, 'hide_title_bar_on_single' ] );
        add_filter( 'astra_the_title_enabled',      [ $this, 'hide_title_bar_on_single' ] );
        add_filter( 'astra_title_bar_enabled',      [ $this, 'hide_title_bar_on_single' ] );
        // Astra Pro 4.x uses this filter
        add_filter( 'astra_addon_banner_visibility',[ $this, 'hide_title_bar_on_single' ] );

        // Breadcrumb items
        add_filter( 'astra_breadcrumb_trail_items', [ $this, 'event_breadcrumbs' ], 10, 2 );

        // Astra Pro — register our CPT for the Layouts meta-box
        add_filter( 'astra_metabox_page_types', [ $this, 'register_for_metabox' ] );

        // Remove Astra's default entry-header inside the loop for single events
        // so the title is not duplicated (we render it in our hero section)
        add_action( 'astra_single_post_before_content', [ $this, 'maybe_suppress_entry_header' ] );
    }

    // ─── CSS Variable Bridge ──────────────────────────────────────────────

    /**
     * Maps Astra's design tokens into --ce-* custom properties so the plugin
     * automatically inherits the active Astra colour palette and typography.
     */
    public function bridge_css_vars(): void {
        ?>
        <style id="ce-astra-bridge">
        :root {
            /* Colour — fall back to plugin defaults when Astra vars are absent */
            --ce-primary:    var(--ast-global-color-0,    #3b82f6);
            --ce-primary-dk: var(--ast-global-color-1,    #1d4ed8);
            --ce-text:       var(--ast-global-color-4,    #1e293b);
            --ce-text-muted: var(--ast-global-color-5,    #64748b);
            --ce-border:     var(--ast-border-color,      #e2e8f0);
            --ce-bg:         var(--ast-main-header-bg-color-responsive, #f8fafc);

            /* Typography — inherit Astra's font stack */
            --ce-font-family:         var(--ast-body-font-family,    inherit);
            --ce-heading-font-family: var(--ast-heading-font-family, inherit);

            /* Spacing — mirror Astra's content horizontal padding */
            --ce-content-padding: var(--ast-content-spacing, 20px);
        }

        /* Apply inherited fonts */
        .ce-timeline-wrap, .ce-overview-wrap, .ce-cards-wrap,
        .ce-event-hero, .ce-event-body-wrap, .ce-archive-wrap,
        .ce-subscribe-wrap {
            font-family: var(--ce-font-family);
        }
        .ce-event-title, .ce-card-title, .ce-month-label,
        .ce-archive-title, .ce-sidebar-card h3 {
            font-family: var(--ce-heading-font-family);
        }

        /* ── Primary-colour accent for light tint ──────────────────────── */
        .ce-filter-btn.active, .ce-filter-btn:hover,
        .ce-btn-primary, .ce-btn-primary:hover {
            background: var(--ce-primary);
            border-color: var(--ce-primary);
        }

        /* ── Full-bleed hero inside Astra content column ───────────────── */
        /* Breaks the hero out of the narrow content column to span the viewport */
        .ce-single-event .ce-event-hero {
            width:       100vw;
            margin-left: calc(50% - 50vw);
            margin-right:calc(50% - 50vw);
            border-radius: 0;
        }

        /* Compensate when Astra scrollbar-width offset is applied */
        @supports (scrollbar-gutter: stable) {
            .ce-single-event .ce-event-hero {
                width:        calc(100vw - var(--ast-scrollbar-width, 0px));
                margin-left:  calc(50% - 50vw + var(--ast-scrollbar-width,0px) / 2);
                margin-right: calc(50% - 50vw + var(--ast-scrollbar-width,0px) / 2);
            }
        }

        /* ── Astra "No Sidebar" layout — let our 2-col grid handle layout ─ */
        body.single-club_event #primary.content-area {
            width: 100%;
            max-width: 100%;
            float: none;
        }
        body.single-club_event #secondary { display: none; }

        /* ── Respect Astra container for archive ───────────────────────── */
        body.post-type-archive-club_event .ast-container > #primary,
        body.tax-event_category .ast-container > #primary,
        body.tax-event_tag      .ast-container > #primary {
            flex: 1;
            min-width: 0;
        }

        /* ── Astra separate-container (boxed layout) border fix ─────────── */
        .ast-separate-container .ce-event-hero {
            border-radius: 0;
        }

        /* ── Astra transparent / sticky header — z-index safety ────────── */
        #masthead, .main-header-bar, .ast-primary-sticky-header {
            z-index: 1000 !important;
        }
        .ce-filter-bar, .ce-cal-nav {
            z-index: 10;
        }

        /* ── Astra Pro — hide entry-title on single events (in our hero) ── */
        body.single-club_event .ast-separate-container .ast-article-single .entry-header,
        body.single-club_event .ast-plain-container .ast-article-single .entry-header,
        body.single-club_event .entry-header .entry-title { display: none; }

        /* ── Astra button class passthrough ─────────────────────────────── */
        .ce-btn { letter-spacing: var(--ast-button-letter-spacing, normal); }
        .ce-btn-primary {
            border-radius: var(--ast-button-border-radius, 6px);
        }

        /* ── Astra content spacing alignment ────────────────────────────── */
        .ce-archive-wrap, .ce-timeline-wrap, .ce-cards-wrap, .ce-overview-wrap {
            padding-top: var(--ast-section-spacing, 2rem);
        }
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

        // Event status
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
        if ( is_post_type_archive( 'club_event' ) || is_tax( [ 'event_category', 'event_tag' ] ) ) {
            $classes[] = 'ce-archive-page';
        }
        return $classes;
    }

    // ─── Layout ───────────────────────────────────────────────────────────

    /**
     * Force "no sidebar" layout on single event pages — our template manages
     * its own two-column layout inside the content column.
     */
    public function single_event_layout( string $layout ): string {
        if ( is_singular( 'club_event' ) ) {
            return 'no-sidebar';
        }
        return $layout;
    }

    /** Give the archive the full content width when there's no sidebar. */
    public function archive_content_width( string $width ): string {
        if ( is_post_type_archive( 'club_event' ) || is_tax( [ 'event_category', 'event_tag' ] ) ) {
            return '100';
        }
        return $width;
    }

    // ─── Title Bar ────────────────────────────────────────────────────────

    /**
     * Hides Astra's page-title banner on single events.
     * The plugin's own hero section renders the title + meta.
     */
    public function hide_title_bar_on_single( $value ) {
        if ( is_singular( 'club_event' ) ) {
            return false;
        }
        return $value;
    }

    /** Remove the entry-header inside the loop (duplicate of hero title). */
    public function maybe_suppress_entry_header(): void {
        if ( ! is_singular( 'club_event' ) ) {
            return;
        }
        // Astra outputs entry-header via astra_single_post_before_content
        remove_action( 'astra_single_post_before_content', 'astra_entry_header_template' );
    }

    // ─── Breadcrumbs ─────────────────────────────────────────────────────

    /**
     * Inject event-specific breadcrumb items into Astra's breadcrumb trail.
     *
     * Trail becomes: Home > Events > [Category] > Event Title
     */
    public function event_breadcrumbs( array $items, array $args ): array {
        if ( ! is_singular( 'club_event' ) && ! is_post_type_archive( 'club_event' ) ) {
            return $items;
        }

        $archive_url   = get_post_type_archive_link( 'club_event' );
        $archive_label = __( 'Events', 'club-events' );

        // Build fresh trail rather than relying on Astra's auto-detection
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

    /**
     * Register the `club_event` post type with Astra Pro's page-specific
     * settings meta-box so users can override layout per-event.
     */
    public function register_for_metabox( array $types ): array {
        $types[] = 'club_event';
        return $types;
    }
}
