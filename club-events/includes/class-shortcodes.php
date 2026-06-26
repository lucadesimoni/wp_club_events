<?php
defined( 'ABSPATH' ) || exit;

class CE_Shortcodes {

    public function __construct() {
        add_shortcode( 'club_events_timeline', [ $this, 'timeline' ] );
        add_shortcode( 'club_events_overview', [ $this, 'overview' ] );
        add_shortcode( 'club_events_list', [ $this, 'list_view' ] );
        add_shortcode( 'club_events_cards', [ $this, 'cards' ] );
        add_shortcode( 'club_events_subscribe', [ $this, 'subscribe_form' ] );
        add_shortcode( 'club_events_yearly', [ $this, 'yearly' ] );
        add_shortcode( 'club_events_tiles', [ $this, 'tiles' ] );

        add_action( 'init', [ $this, 'register_blocks' ] );
    }

    public function register_blocks() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        $editor = [ 'editor_script' => 'club-events-blocks', 'editor_style' => 'club-events-editor' ];

        register_block_type( 'club-events/timeline', array_merge( $editor, [
            'render_callback' => [ $this, 'timeline' ],
            'attributes'      => [
                'category'    => [ 'type' => 'string',  'default' => '' ],
                'event_type'  => [ 'type' => 'string',  'default' => '' ],
                'filter_by'   => [ 'type' => 'string',  'default' => 'category' ],
                'limit'       => [ 'type' => 'number',  'default' => 20 ],
                'show_past'   => [ 'type' => 'boolean', 'default' => false ],
                'show_filter' => [ 'type' => 'boolean', 'default' => true ],
            ],
        ] ) );

        register_block_type( 'club-events/overview', array_merge( $editor, [
            'render_callback' => [ $this, 'overview' ],
            'attributes'      => [
                'category'    => [ 'type' => 'string',  'default' => '' ],
                'event_type'  => [ 'type' => 'string',  'default' => '' ],
                'filter_by'   => [ 'type' => 'string',  'default' => 'category' ],
                'show_filter' => [ 'type' => 'boolean', 'default' => true ],
            ],
        ] ) );

        register_block_type( 'club-events/cards', array_merge( $editor, [
            'render_callback' => [ $this, 'cards' ],
            'attributes'      => [
                'category'    => [ 'type' => 'string',  'default' => '' ],
                'event_type'  => [ 'type' => 'string',  'default' => '' ],
                'filter_by'   => [ 'type' => 'string',  'default' => 'category' ],
                'limit'       => [ 'type' => 'number',  'default' => 6 ],
                'columns'     => [ 'type' => 'number',  'default' => 3 ],
                'show_past'   => [ 'type' => 'boolean', 'default' => false ],
                'show_filter' => [ 'type' => 'boolean', 'default' => true ],
                'show_image'  => [ 'type' => 'boolean', 'default' => true ],
            ],
        ] ) );

        register_block_type( 'club-events/list', array_merge( $editor, [
            'render_callback' => [ $this, 'list_view' ],
            'attributes'      => [
                'category'    => [ 'type' => 'string',  'default' => '' ],
                'event_type'  => [ 'type' => 'string',  'default' => '' ],
                'limit'       => [ 'type' => 'number',  'default' => 5 ],
                'show_past'   => [ 'type' => 'boolean', 'default' => false ],
            ],
        ] ) );

        register_block_type( 'club-events/subscribe', array_merge( $editor, [
            'render_callback' => [ $this, 'subscribe_form' ],
            'attributes'      => [],
        ] ) );

        register_block_type( 'club-events/yearly', array_merge( $editor, [
            'render_callback' => [ $this, 'yearly' ],
            'attributes'      => [
                'category'   => [ 'type' => 'string',  'default' => '' ],
                'event_type' => [ 'type' => 'string',  'default' => '' ],
                'year'       => [ 'type' => 'number',  'default' => 0 ],
            ],
        ] ) );

        register_block_type( 'club-events/tiles', array_merge( $editor, [
            'render_callback' => [ $this, 'tiles' ],
            'attributes'      => [
                'event_type'    => [ 'type' => 'string',  'default' => '' ],
                'category'      => [ 'type' => 'string',  'default' => '' ],
                'limit'         => [ 'type' => 'number',  'default' => 6 ],
                'columns'       => [ 'type' => 'number',  'default' => 3 ],
                'show_image'    => [ 'type' => 'boolean', 'default' => true ],
                'show_excerpt'  => [ 'type' => 'boolean', 'default' => true ],
                'show_location' => [ 'type' => 'boolean', 'default' => false ],
                'show_time'     => [ 'type' => 'boolean', 'default' => false ],
                'show_types'    => [ 'type' => 'boolean', 'default' => false ],
                'cta'           => [ 'type' => 'string',  'default' => 'Weiterlesen' ],
            ],
        ] ) );

        register_block_type( 'club-events/submit', array_merge( $editor, [
            'render_callback' => [ 'CE_Frontend_Submit', 'render_form_static' ],
            'attributes'      => [],
        ] ) );

        register_block_type( 'club-events/my-events', array_merge( $editor, [
            'render_callback' => [ 'CE_Frontend_Submit', 'render_my_events_static' ],
            'attributes'      => [],
        ] ) );

        wp_register_script(
            'club-events-blocks',
            CE_PLUGIN_URL . 'blocks/index.js',
            [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ],
            CE_VERSION,
            true
        );
    }

    public function timeline( $atts = [], $content = '' ) {
        $atts = is_array( $atts ) ? $atts : [];
        $atts = shortcode_atts( [
            'category'    => '',
            'event_type'  => '',
            'filter_by'   => 'category',
            'limit'       => 20,
            'show_past'   => false,
            'show_filter' => true,
        ], $atts, 'club_events_timeline' );

        $query_args = [
            'posts_per_page' => (int) $atts['limit'],
        ];

        if ( ! $atts['show_past'] ) {
            $query_args['from'] = date( 'Y-m-d H:i:s' );
        }

        if ( $atts['category'] ) {
            $query_args['tax_query'] = [ [
                'taxonomy' => 'event_category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $atts['category'] ),
            ] ];
        }

        if ( $atts['event_type'] ) {
            $query_args['event_type'] = sanitize_text_field( $atts['event_type'] );
        }

        $posts  = CE_CPT::get_events( $query_args );
        $events = array_map( fn( $p ) => CE_CPT::format_event( $p->ID ), $posts );

        $filter_taxonomy = ( 'event_type' === $atts['filter_by'] ) ? 'event_type' : 'event_category';
        $filter_terms    = get_terms( [ 'taxonomy' => $filter_taxonomy, 'hide_empty' => true ] );

        ob_start();
        ?>
        <div class="ce-timeline-wrap" data-ce-component="timeline">
            <?php if ( ! empty( $atts['show_filter'] ) && ! is_wp_error( $filter_terms ) && count( $filter_terms ) > 1 ) : ?>
            <div class="ce-filter-bar">
                <button class="ce-filter-btn active" data-category=""><?php esc_html_e( 'All', 'club-events' ); ?></button>
                <?php foreach ( $filter_terms as $term ) : ?>
                <button class="ce-filter-btn" data-category="<?php echo esc_attr( $term->slug ); ?>">
                    <?php echo esc_html( $term->name ); ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="ce-timeline" id="ce-timeline">
                <?php if ( empty( $events ) ) : ?>
                <p class="ce-empty"><?php esc_html_e( 'No upcoming events.', 'club-events' ); ?></p>
                <?php else : ?>
                    <?php
                    $current_month = '';
                    foreach ( $events as $event ) :
                        $month = $event['start'] ? date_i18n( 'F Y', strtotime( $event['start'] ) ) : '';
                        if ( $month !== $current_month ) :
                            if ( $current_month ) echo '</div>'; // close prev month group
                            $current_month = $month;
                    ?>
                    <div class="ce-month-group" data-month="<?php echo esc_attr( $month ); ?>">
                        <h3 class="ce-month-label"><?php echo esc_html( $month ); ?></h3>
                    <?php endif; ?>

                    <?php
                    $filter_slugs = ( 'event_type' === $atts['filter_by'] )
                        ? implode( ' ', array_column( $event['types'], 'slug' ) )
                        : implode( ' ', array_column( $event['categories'], 'slug' ) );
                    ?>
                    <div class="ce-timeline-item" data-category="<?php echo esc_attr( $filter_slugs ); ?>"
                         style="--ce-color: <?php echo esc_attr( $event['color'] ); ?>">
                        <div class="ce-timeline-dot"></div>
                        <div class="ce-timeline-content">
                            <div class="ce-timeline-date">
                                <?php if ( $event['start'] ) : ?>
                                <span class="ce-day"><?php echo esc_html( date_i18n( 'd', strtotime( $event['start'] ) ) ); ?></span>
                                <span class="ce-weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $event['start'] ) ) ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="ce-timeline-body">
                                <h4 class="ce-event-title">
                                    <a href="<?php echo esc_url( $event['url'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a>
                                </h4>
                                <div class="ce-event-meta">
                                    <?php if ( $event['start'] && ! $event['allDay'] ) : ?>
                                    <span class="ce-meta-item ce-meta-time">
                                        <svg viewBox="0 0 16 16"><circle cx="8" cy="8" r="7" stroke="currentColor" fill="none"/><path d="M8 4v4l3 2" stroke="currentColor" stroke-linecap="round"/></svg>
                                        <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $event['start'] ) ) ); ?>
                                        <?php if ( $event['end'] ) : ?>
                                        – <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $event['end'] ) ) ); ?>
                                        <?php endif; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ( $event['location'] ) : ?>
                                    <span class="ce-meta-item ce-meta-location">
                                        <svg viewBox="0 0 16 16"><path d="M8 1a5 5 0 0 1 5 5c0 4-5 9-5 9S3 10 3 6a5 5 0 0 1 5-5z" stroke="currentColor" fill="none"/><circle cx="8" cy="6" r="1.5" fill="currentColor"/></svg>
                                        <?php if ( $event['locationUrl'] ) : ?>
                                        <a href="<?php echo esc_url( $event['locationUrl'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $event['location'] ); ?></a>
                                        <?php else : ?>
                                        <?php echo esc_html( $event['location'] ); ?>
                                        <?php endif; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php foreach ( $event['categories'] as $cat ) : ?>
                                    <span class="ce-meta-item ce-category-badge"><?php echo esc_html( $cat['name'] ); ?></span>
                                    <?php endforeach; ?>
                                    <?php foreach ( $event['types'] as $type ) : ?>
                                    <span class="ce-meta-item ce-type-badge"><?php echo esc_html( $type['name'] ); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ( $event['excerpt'] ) : ?>
                                <p class="ce-event-excerpt"><?php echo esc_html( $event['excerpt'] ); ?></p>
                                <?php endif; ?>
                                <div class="ce-event-actions">
                                    <a href="<?php echo esc_url( $event['url'] ); ?>" class="ce-btn ce-btn-primary">
                                        <?php esc_html_e( 'Details', 'club-events' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( CE_ICS_Export::get_single_url( $event['id'] ) ); ?>" class="ce-btn ce-btn-outline">
                                        <svg viewBox="0 0 16 16" width="14" height="14"><rect x="2" y="3" width="12" height="12" rx="1.5" stroke="currentColor" fill="none"/><path d="M5 2v2M11 2v2M2 7h12" stroke="currentColor" stroke-linecap="round"/></svg>
                                        <?php esc_html_e( 'Add to Calendar', 'club-events' ); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if ( $current_month ) echo '</div>'; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function overview( $atts = [], $content = '' ) {
        $atts = is_array( $atts ) ? $atts : [];
        $atts = shortcode_atts( [
            'category'    => '',
            'event_type'  => '',
            'filter_by'   => 'category',
            'show_filter' => true,
        ], $atts, 'club_events_overview' );

        $year  = isset( $_GET['ce_year'] )  ? (int) $_GET['ce_year']  : (int) date( 'Y' );
        $month = isset( $_GET['ce_month'] ) ? (int) $_GET['ce_month'] : (int) date( 'n' );

        $month = max( 1, min( 12, $month ) );

        $first_day   = mktime( 0, 0, 0, $month, 1, $year );
        $days_in_mon = (int) date( 't', $first_day );
        $start_dow   = (int) date( 'N', $first_day ) % 7; // 0=Sun

        $from = date( 'Y-m-d H:i:s', $first_day );
        $to   = date( 'Y-m-d H:i:s', mktime( 23, 59, 59, $month, $days_in_mon, $year ) );

        $query_args = [ 'from' => $from, 'to' => $to, 'posts_per_page' => 200 ];
        if ( $atts['category'] ) {
            $query_args['tax_query'] = [ [ 'taxonomy' => 'event_category', 'field' => 'slug', 'terms' => sanitize_text_field( $atts['category'] ) ] ];
        }
        if ( $atts['event_type'] ) {
            $query_args['event_type'] = sanitize_text_field( $atts['event_type'] );
        }

        $posts  = CE_CPT::get_events( $query_args );
        $events_by_day = [];
        foreach ( $posts as $post ) {
            $ev  = CE_CPT::format_event( $post->ID );
            $day = (int) date( 'j', strtotime( $ev['start'] ) );
            $events_by_day[ $day ][] = $ev;
        }

        $prev_month = $month === 1 ? 12 : $month - 1;
        $prev_year  = $month === 1 ? $year - 1 : $year;
        $next_month = $month === 12 ? 1 : $month + 1;
        $next_year  = $month === 12 ? $year + 1 : $year;

        $current_url = remove_query_arg( [ 'ce_year', 'ce_month' ] );

        $filter_taxonomy = ( 'event_type' === $atts['filter_by'] ) ? 'event_type' : 'event_category';
        $filter_terms    = get_terms( [ 'taxonomy' => $filter_taxonomy, 'hide_empty' => true ] );

        ob_start();
        ?>
        <div class="ce-overview-wrap" data-ce-component="overview">
            <?php if ( ! empty( $atts['show_filter'] ) && ! is_wp_error( $filter_terms ) && count( $filter_terms ) > 1 ) : ?>
            <div class="ce-filter-bar">
                <button class="ce-filter-btn active" data-category=""><?php esc_html_e( 'All', 'club-events' ); ?></button>
                <?php foreach ( $filter_terms as $term ) : ?>
                <button class="ce-filter-btn" data-category="<?php echo esc_attr( $term->slug ); ?>">
                    <?php echo esc_html( $term->name ); ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="ce-cal-nav">
                <a href="<?php echo esc_url( add_query_arg( [ 'ce_year' => $prev_year, 'ce_month' => $prev_month ], $current_url ) ); ?>" class="ce-cal-nav-btn">
                    <svg viewBox="0 0 16 16" width="18"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                </a>
                <h3 class="ce-cal-title"><?php echo esc_html( date_i18n( 'F Y', $first_day ) ); ?></h3>
                <a href="<?php echo esc_url( add_query_arg( [ 'ce_year' => $next_year, 'ce_month' => $next_month ], $current_url ) ); ?>" class="ce-cal-nav-btn">
                    <svg viewBox="0 0 16 16" width="18"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                </a>
            </div>

            <div class="ce-calendar-grid">
                <?php
                $day_names = [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ];
                foreach ( $day_names as $dn ) {
                    echo '<div class="ce-cal-header">' . esc_html( $dn ) . '</div>';
                }

                for ( $i = 0; $i < $start_dow; $i++ ) {
                    echo '<div class="ce-cal-day ce-cal-empty"></div>';
                }

                $today = (int) date( 'j' );
                $today_month = (int) date( 'n' );
                $today_year  = (int) date( 'Y' );

                for ( $day = 1; $day <= $days_in_mon; $day++ ) {
                    $is_today = ( $day === $today && $month === $today_month && $year === $today_year );
                    $has_events = ! empty( $events_by_day[ $day ] );
                    $classes = 'ce-cal-day' . ( $is_today ? ' ce-cal-today' : '' ) . ( $has_events ? ' ce-cal-has-events' : '' );
                    echo '<div class="' . esc_attr( $classes ) . '">';
                    echo '<span class="ce-cal-day-num">' . esc_html( $day ) . '</span>';
                    if ( $has_events ) {
                        echo '<div class="ce-cal-events">';
                        foreach ( $events_by_day[ $day ] as $ev ) {
                            $ev_filter_slugs = ( 'event_type' === $atts['filter_by'] )
                                ? implode( ' ', array_column( $ev['types'], 'slug' ) )
                                : implode( ' ', array_column( $ev['categories'], 'slug' ) );
                            echo '<a href="' . esc_url( $ev['url'] ) . '" class="ce-cal-event" '
                                . 'style="--ce-color:' . esc_attr( $ev['color'] ) . '" '
                                . 'data-category="' . esc_attr( $ev_filter_slugs ) . '" '
                                . 'title="' . esc_attr( $ev['title'] ) . '">'
                                . '<span class="ce-cal-event-dot"></span>'
                                . '<span class="ce-cal-event-title">' . esc_html( $ev['title'] ) . '</span>'
                                . '</a>';
                        }
                        echo '</div>';
                    }
                    echo '</div>';
                }
                ?>
            </div>

            <?php
            // Events list for selected month
            if ( ! empty( $posts ) ) :
            ?>
            <div class="ce-overview-list">
                <h4 class="ce-overview-list-title"><?php echo esc_html( date_i18n( 'F Y', $first_day ) ); ?></h4>
                <?php foreach ( $posts as $post ) :
                    $ev = CE_CPT::format_event( $post->ID );
                ?>
                <?php
                $list_filter_slugs = ( 'event_type' === $atts['filter_by'] )
                    ? implode( ' ', array_column( $ev['types'], 'slug' ) )
                    : implode( ' ', array_column( $ev['categories'], 'slug' ) );
                ?>
                <div class="ce-list-item" data-category="<?php echo esc_attr( $list_filter_slugs ); ?>"
                     style="--ce-color:<?php echo esc_attr( $ev['color'] ); ?>">
                    <div class="ce-list-date">
                        <span class="ce-day"><?php echo esc_html( date_i18n( 'd', strtotime( $ev['start'] ) ) ); ?></span>
                        <span class="ce-weekday"><?php echo esc_html( date_i18n( 'D', strtotime( $ev['start'] ) ) ); ?></span>
                    </div>
                    <div class="ce-list-body">
                        <a href="<?php echo esc_url( $ev['url'] ); ?>" class="ce-list-title"><?php echo esc_html( $ev['title'] ); ?></a>
                        <?php if ( $ev['location'] ) : ?>
                        <span class="ce-list-location"><?php echo esc_html( $ev['location'] ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="ce-list-actions">
                        <a href="<?php echo esc_url( CE_ICS_Export::get_single_url( $ev['id'] ) ); ?>" class="ce-btn ce-btn-sm ce-btn-outline" title="<?php esc_attr_e( 'Add to Calendar', 'club-events' ); ?>">
                            <svg viewBox="0 0 16 16" width="14" height="14"><rect x="2" y="3" width="12" height="12" rx="1.5" stroke="currentColor" fill="none"/><path d="M5 2v2M11 2v2M2 7h12" stroke="currentColor" stroke-linecap="round"/></svg>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function list_view( $atts = [] ) {
        $atts = is_array( $atts ) ? $atts : [];
        $atts = shortcode_atts( [
            'category'   => '',
            'event_type' => '',
            'limit'      => 5,
            'show_past'  => false,
        ], $atts, 'club_events_list' );

        $query_args = [ 'posts_per_page' => (int) $atts['limit'] ];
        if ( ! $atts['show_past'] ) {
            $query_args['from'] = date( 'Y-m-d H:i:s' );
        }
        if ( $atts['category'] ) {
            $query_args['tax_query'] = [ [ 'taxonomy' => 'event_category', 'field' => 'slug', 'terms' => sanitize_text_field( $atts['category'] ) ] ];
        }
        if ( $atts['event_type'] ) {
            $query_args['event_type'] = sanitize_text_field( $atts['event_type'] );
        }

        $posts = CE_CPT::get_events( $query_args );

        ob_start();
        if ( empty( $posts ) ) {
            echo '<p class="ce-empty">' . esc_html__( 'No upcoming events.', 'club-events' ) . '</p>';
        } else {
            echo '<ul class="ce-event-list">';
            foreach ( $posts as $post ) {
                $ev   = CE_CPT::format_event( $post->ID );
                $date = $ev['start'] ? date_i18n( get_option( 'date_format' ), strtotime( $ev['start'] ) ) : '';
                echo '<li class="ce-event-list-item" style="--ce-color:' . esc_attr( $ev['color'] ) . '">';
                echo '<span class="ce-event-list-date">' . esc_html( $date ) . '</span>';
                echo '<a href="' . esc_url( $ev['url'] ) . '">' . esc_html( $ev['title'] ) . '</a>';
                if ( $ev['location'] ) {
                    echo '<span class="ce-event-list-loc"> · ' . esc_html( $ev['location'] ) . '</span>';
                }
                echo '</li>';
            }
            echo '</ul>';
        }
        return ob_get_clean();
    }

    public function cards( $atts = [], $content = '' ) {
        $atts = is_array( $atts ) ? $atts : [];
        $atts = shortcode_atts( [
            'category'    => '',
            'event_type'  => '',
            'filter_by'   => 'category',
            'limit'       => 6,
            'columns'     => 3,
            'show_past'   => false,
            'show_filter' => true,
            'show_image'  => true,
        ], $atts, 'club_events_cards' );

        $cols = max( 1, min( 4, (int) $atts['columns'] ) );

        $query_args = [ 'posts_per_page' => (int) $atts['limit'] ];
        if ( ! $atts['show_past'] ) {
            $query_args['from'] = date( 'Y-m-d H:i:s' );
        }
        if ( $atts['category'] ) {
            $query_args['tax_query'] = [ [
                'taxonomy' => 'event_category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $atts['category'] ),
            ] ];
        }
        if ( $atts['event_type'] ) {
            $query_args['event_type'] = sanitize_text_field( $atts['event_type'] );
        }

        $posts           = CE_CPT::get_events( $query_args );
        $events          = array_map( fn( $p ) => CE_CPT::format_event( $p->ID ), $posts );
        $filter_taxonomy = ( 'event_type' === $atts['filter_by'] ) ? 'event_type' : 'event_category';
        $filter_terms    = get_terms( [ 'taxonomy' => $filter_taxonomy, 'hide_empty' => true ] );

        ob_start();
        ?>
        <div class="ce-cards-wrap" data-ce-component="cards" style="--ce-cols:<?php echo esc_attr( $cols ); ?>">

            <?php if ( ! empty( $atts['show_filter'] ) && ! is_wp_error( $filter_terms ) && count( $filter_terms ) > 1 ) : ?>
            <div class="ce-filter-bar">
                <button class="ce-filter-btn active" data-category=""><?php esc_html_e( 'All', 'club-events' ); ?></button>
                <?php foreach ( $filter_terms as $term ) : ?>
                <button class="ce-filter-btn" data-category="<?php echo esc_attr( $term->slug ); ?>">
                    <?php echo esc_html( $term->name ); ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ( empty( $events ) ) : ?>
            <p class="ce-empty"><?php esc_html_e( 'No upcoming events.', 'club-events' ); ?></p>
            <?php else : ?>
            <div class="ce-cards-grid">
                <?php foreach ( $events as $event ) :
                    $start_ts   = $event['start'] ? strtotime( $event['start'] ) : null;
                    $end_ts     = $event['end']   ? strtotime( $event['end'] )   : null;
                    $month      = $start_ts ? date_i18n( 'M', $start_ts ) : '';
                    $day        = $start_ts ? date_i18n( 'j', $start_ts ) : '';
                    $weekday    = $start_ts ? date_i18n( 'l', $start_ts ) : '';
                    $time_start = ( $start_ts && ! $event['allDay'] ) ? date_i18n( get_option( 'time_format' ), $start_ts ) : '';
                    $time_end   = ( $end_ts   && ! $event['allDay'] ) ? date_i18n( get_option( 'time_format' ), $end_ts )   : '';
                    $filter_slugs = ( 'event_type' === $atts['filter_by'] )
                        ? implode( ' ', array_column( $event['types'], 'slug' ) )
                        : implode( ' ', array_column( $event['categories'], 'slug' ) );
                ?>
                <article class="ce-card-item"
                         data-category="<?php echo esc_attr( $filter_slugs ); ?>"
                         style="--ce-color:<?php echo esc_attr( $event['color'] ); ?>">

                    <a href="<?php echo esc_url( $event['url'] ); ?>" class="ce-card-inner">

                        <?php if ( ! empty( $atts['show_image'] ) && $event['thumbnail'] ) : ?>
                        <div class="ce-card-img">
                            <img src="<?php echo esc_url( $event['thumbnail'] ); ?>"
                                 alt="<?php echo esc_attr( $event['title'] ); ?>"
                                 loading="lazy">
                            <?php if ( ! empty( $event['categories'] ) ) : ?>
                            <div class="ce-card-cats">
                                <?php foreach ( $event['categories'] as $cat ) : ?>
                                <span class="ce-category-badge"><?php echo esc_html( $cat['name'] ); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php elseif ( ! empty( $atts['show_image'] ) ) : ?>
                        <div class="ce-card-img ce-card-img--placeholder">
                            <div class="ce-card-placeholder-inner" style="background:linear-gradient(135deg,<?php echo esc_attr( $event['color'] ); ?> 0%,<?php echo esc_attr( $event['color'] ); ?>aa 100%)">
                                <svg viewBox="0 0 48 48" width="40" height="40" fill="none">
                                    <rect x="6" y="8" width="36" height="36" rx="4" stroke="rgba(255,255,255,.7)" stroke-width="2"/>
                                    <path d="M16 6v6M32 6v6M6 20h36" stroke="rgba(255,255,255,.7)" stroke-width="2" stroke-linecap="round"/>
                                    <rect x="14" y="26" width="8" height="8" rx="1" fill="rgba(255,255,255,.5)"/>
                                </svg>
                            </div>
                            <?php if ( ! empty( $event['categories'] ) ) : ?>
                            <div class="ce-card-cats">
                                <?php foreach ( $event['categories'] as $cat ) : ?>
                                <span class="ce-category-badge"><?php echo esc_html( $cat['name'] ); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="ce-card-body">
                            <?php if ( $start_ts ) : ?>
                            <div class="ce-card-date-badge">
                                <span class="ce-card-date-day"><?php echo esc_html( $day ); ?></span>
                                <span class="ce-card-date-month"><?php echo esc_html( $month ); ?></span>
                            </div>
                            <?php endif; ?>

                            <h3 class="ce-card-title"><?php echo esc_html( $event['title'] ); ?></h3>

                            <div class="ce-card-meta">
                                <?php if ( $weekday && $time_start ) : ?>
                                <div class="ce-card-meta-row">
                                    <svg viewBox="0 0 16 16" width="13" height="13" fill="none"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.2"/><path d="M8 4.5V8l2.5 1.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                                    <span><?php echo esc_html( $weekday ); ?>, <?php echo esc_html( $time_start ); ?><?php echo $time_end ? ' – ' . esc_html( $time_end ) : ''; ?></span>
                                </div>
                                <?php elseif ( $weekday ) : ?>
                                <div class="ce-card-meta-row">
                                    <svg viewBox="0 0 16 16" width="13" height="13" fill="none"><rect x="2" y="3" width="12" height="11" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M5 2v2M11 2v2M2 7h12" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                                    <span><?php echo esc_html( $event['allDay'] ? $weekday . ' · ' . esc_html__( 'All day', 'club-events' ) : $weekday ); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if ( $event['location'] ) : ?>
                                <div class="ce-card-meta-row">
                                    <svg viewBox="0 0 16 16" width="13" height="13" fill="none"><path d="M8 1.5a4.5 4.5 0 0 1 4.5 4.5c0 3.5-4.5 8.5-4.5 8.5S3.5 9.5 3.5 6A4.5 4.5 0 0 1 8 1.5z" stroke="currentColor" stroke-width="1.2"/><circle cx="8" cy="6" r="1.5" fill="currentColor"/></svg>
                                    <span><?php echo esc_html( $event['location'] ); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if ( $event['excerpt'] ) : ?>
                            <p class="ce-card-excerpt"><?php echo esc_html( $event['excerpt'] ); ?></p>
                            <?php endif; ?>

                            <?php if ( ! empty( $event['types'] ) ) : ?>
                            <div class="ce-card-type-badges">
                                <?php foreach ( $event['types'] as $type ) : ?>
                                <span class="ce-type-badge"><?php echo esc_html( $type['name'] ); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <div class="ce-card-footer">
                                <span class="ce-card-cta"><?php esc_html_e( 'More details', 'club-events' ); ?> →</span>
                                <a href="<?php echo esc_url( CE_ICS_Export::get_single_url( $event['id'] ) ); ?>"
                                   class="ce-card-ics"
                                   onclick="event.stopPropagation()"
                                   title="<?php esc_attr_e( 'Add to Calendar', 'club-events' ); ?>">
                                    <svg viewBox="0 0 16 16" width="14" height="14" fill="none"><rect x="2" y="3" width="12" height="11" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M5 2v2M11 2v2M2 7h12" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><path d="M8 10V8M8 12v.01" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                                </a>
                            </div>
                        </div>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function yearly( $atts = [] ) {
        $atts = is_array( $atts ) ? $atts : [];
        $atts = shortcode_atts( [
            'category'   => '',
            'event_type' => '',
            'year'       => 0,
        ], $atts, 'club_events_yearly' );

        $year = (int) $atts['year'];
        if ( ! $year ) {
            $year = isset( $_GET['ce_year'] ) ? (int) $_GET['ce_year'] : (int) date( 'Y' );
        }

        $from = "$year-01-01 00:00:00";
        $to   = "$year-12-31 23:59:59";

        $query_args = [ 'from' => $from, 'to' => $to, 'posts_per_page' => 500 ];
        if ( $atts['category'] ) {
            $query_args['tax_query'] = [ [ 'taxonomy' => 'event_category', 'field' => 'slug', 'terms' => sanitize_text_field( $atts['category'] ) ] ];
        }
        if ( $atts['event_type'] ) {
            $query_args['event_type'] = sanitize_text_field( $atts['event_type'] );
        }

        $posts = CE_CPT::get_events( $query_args );
        $events_by_month = [];
        foreach ( $posts as $post ) {
            $ev = CE_CPT::format_event( $post->ID );
            $m  = (int) date( 'n', strtotime( $ev['start'] ) );
            $events_by_month[ $m ][] = $ev;
        }

        $current_url = remove_query_arg( 'ce_year' );
        $prev_year   = $year - 1;
        $next_year   = $year + 1;

        ob_start();
        ?>
        <div class="ce-yearly-wrap" data-ce-component="yearly">
            <div class="ce-cal-nav">
                <a href="<?php echo esc_url( add_query_arg( 'ce_year', $prev_year, $current_url ) ); ?>" class="ce-cal-nav-btn">
                    <svg viewBox="0 0 16 16" width="18"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                </a>
                <h3 class="ce-cal-title"><?php echo esc_html( $year ); ?></h3>
                <a href="<?php echo esc_url( add_query_arg( 'ce_year', $next_year, $current_url ) ); ?>" class="ce-cal-nav-btn">
                    <svg viewBox="0 0 16 16" width="18"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                </a>
            </div>

            <?php
            $month_names = [];
            for ( $i = 1; $i <= 12; $i++ ) {
                $month_names[ $i ] = date_i18n( 'F', mktime( 0, 0, 0, $i, 1 ) );
            }

            $today_str = date( 'Y-m-d' );

            foreach ( $month_names as $m => $name ) :
                $events = $events_by_month[ $m ] ?? [];
                $month_class = 'ce-yearly-month' . ( empty( $events ) ? ' ce-yearly-month--empty' : '' );
            ?>
            <div class="<?php echo esc_attr( $month_class ); ?>">
                <h4 class="ce-yearly-month-title"><?php echo esc_html( $name ); ?></h4>
                <?php if ( empty( $events ) ) : ?>
                <p class="ce-yearly-no-events"><?php esc_html_e( 'No events', 'club-events' ); ?></p>
                <?php else : ?>
                <div class="ce-yearly-events">
                    <?php foreach ( $events as $ev ) :
                        $start_ts  = strtotime( $ev['start'] );
                        $day       = date_i18n( 'j', $start_ts );
                        $weekday   = date_i18n( 'D', $start_ts );
                        $time      = ( ! $ev['allDay'] ) ? date_i18n( get_option( 'time_format' ), $start_ts ) : '';
                        $is_past   = date( 'Y-m-d', $start_ts ) < $today_str;
                        $row_class = 'ce-yearly-event' . ( $is_past ? ' ce-yearly-event--past' : '' );
                    ?>
                    <a href="<?php echo esc_url( $ev['url'] ); ?>" class="<?php echo esc_attr( $row_class ); ?>"
                       style="--ce-color:<?php echo esc_attr( $ev['color'] ); ?>">
                        <span class="ce-yearly-event-dot"></span>
                        <span class="ce-yearly-event-date">
                            <?php echo esc_html( $weekday ); ?>, <?php echo esc_html( $day ); ?>.
                        </span>
                        <span class="ce-yearly-event-title"><?php echo esc_html( $ev['title'] ); ?></span>
                        <?php if ( $time ) : ?>
                        <span class="ce-yearly-event-time"><?php echo esc_html( $time ); ?></span>
                        <?php endif; ?>
                        <?php if ( $ev['location'] ) : ?>
                        <span class="ce-yearly-event-loc"><?php echo esc_html( $ev['location'] ); ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <div class="ce-yearly-total">
                <?php printf( esc_html__( '%d events in %d', 'club-events' ), count( $posts ), $year ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function tiles( $atts = [], $content = '' ) {
        $atts = is_array( $atts ) ? $atts : [];
        $atts = shortcode_atts( [
            'event_type'    => '',
            'category'      => '',
            'limit'         => 6,
            'columns'       => 3,
            'show_image'    => true,
            'show_excerpt'  => true,
            'show_location' => false,
            'show_time'     => false,
            'show_types'    => false,
            'cta'           => __( 'Weiterlesen', 'club-events' ),
        ], $atts, 'club_events_tiles' );

        $cols = max( 1, min( 4, (int) $atts['columns'] ) );

        $query_args = [
            'posts_per_page' => (int) $atts['limit'],
            'from'           => date( 'Y-m-d H:i:s' ),
        ];
        if ( $atts['event_type'] ) {
            $slugs = array_map( 'trim', explode( ',', sanitize_text_field( $atts['event_type'] ) ) );
            $query_args['event_type'] = $slugs;
        }
        if ( $atts['category'] ) {
            $query_args['tax_query'] = [ [
                'taxonomy' => 'event_category',
                'field'    => 'slug',
                'terms'    => array_map( 'trim', explode( ',', sanitize_text_field( $atts['category'] ) ) ),
            ] ];
        }

        $posts  = CE_CPT::get_events( $query_args );
        $events = array_map( fn( $p ) => CE_CPT::format_event( $p->ID ), $posts );

        ob_start();
        ?>
        <div class="ce-tiles-wrap" data-ce-component="tiles" style="--ce-tile-cols:<?php echo esc_attr( $cols ); ?>">
            <?php if ( empty( $events ) ) : ?>
            <p class="ce-empty"><?php esc_html_e( 'No upcoming events.', 'club-events' ); ?></p>
            <?php else : ?>
            <div class="ce-tiles">
                <?php foreach ( $events as $event ) :
                    $start_ts   = $event['start'] ? strtotime( $event['start'] ) : null;
                    $end_ts     = $event['end']   ? strtotime( $event['end'] )   : null;
                    $date_label = $start_ts ? date_i18n( 'j. F Y', $start_ts ) : '';
                    $time_start = ( $start_ts && ! $event['allDay'] ) ? date_i18n( get_option( 'time_format' ), $start_ts ) : '';
                    $time_end   = ( $end_ts   && ! $event['allDay'] ) ? date_i18n( get_option( 'time_format' ), $end_ts )   : '';
                ?>
                <a href="<?php echo esc_url( $event['url'] ); ?>" class="ce-tile-card"
                   style="--ce-color:<?php echo esc_attr( $event['color'] ); ?>">

                    <?php if ( ! empty( $atts['show_image'] ) && $event['thumbnail'] ) : ?>
                    <div class="ce-tile-card-img">
                        <img src="<?php echo esc_url( $event['thumbnail'] ); ?>"
                             alt="<?php echo esc_attr( $event['title'] ); ?>"
                             loading="lazy">
                    </div>
                    <?php elseif ( ! empty( $atts['show_image'] ) ) : ?>
                    <div class="ce-tile-card-img ce-tile-card-img--placeholder">
                        <div class="ce-tile-card-placeholder" style="background:linear-gradient(135deg,<?php echo esc_attr( $event['color'] ); ?> 0%,<?php echo esc_attr( $event['color'] ); ?>99 100%)">
                            <svg viewBox="0 0 48 48" width="32" height="32" fill="none">
                                <rect x="6" y="8" width="36" height="36" rx="4" stroke="rgba(255,255,255,.7)" stroke-width="2"/>
                                <path d="M16 6v6M32 6v6M6 20h36" stroke="rgba(255,255,255,.7)" stroke-width="2" stroke-linecap="round"/>
                                <rect x="14" y="26" width="8" height="8" rx="1" fill="rgba(255,255,255,.5)"/>
                            </svg>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="ce-tile-card-body">
                        <?php if ( $date_label ) : ?>
                        <span class="ce-tile-card-date"><?php echo esc_html( strtoupper( $date_label ) ); ?></span>
                        <?php endif; ?>

                        <h3 class="ce-tile-card-title"><?php echo esc_html( $event['title'] ); ?></h3>

                        <?php if ( ! empty( $atts['show_time'] ) && $time_start ) : ?>
                        <span class="ce-tile-card-time"><?php echo esc_html( $time_start ); ?><?php echo $time_end ? ' – ' . esc_html( $time_end ) : ''; ?></span>
                        <?php endif; ?>

                        <?php if ( ! empty( $atts['show_location'] ) && $event['location'] ) : ?>
                        <span class="ce-tile-card-location"><?php echo esc_html( $event['location'] ); ?></span>
                        <?php endif; ?>

                        <?php if ( ! empty( $atts['show_excerpt'] ) && $event['excerpt'] ) : ?>
                        <p class="ce-tile-card-excerpt"><?php echo esc_html( $event['excerpt'] ); ?></p>
                        <?php endif; ?>

                        <?php if ( ! empty( $atts['show_types'] ) && ! empty( $event['types'] ) ) : ?>
                        <div class="ce-tile-card-types">
                            <?php foreach ( $event['types'] as $type ) : ?>
                            <span class="ce-type-badge"><?php echo esc_html( $type['name'] ); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ( $atts['cta'] ) : ?>
                        <span class="ce-tile-card-cta"><?php echo esc_html( $atts['cta'] ); ?> →</span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function subscribe_form( $atts = [] ) {
        if ( get_option( 'ce_subscription_enabled', '1' ) !== '1' ) {
            return '';
        }

        if ( isset( $_GET['ce_subscribed'] ) ) {
            return '<div class="ce-subscribe-success"><p>' . esc_html__( 'You are now subscribed to event updates!', 'club-events' ) . '</p></div>';
        }
        if ( isset( $_GET['ce_unsubscribed'] ) ) {
            return '<div class="ce-subscribe-success"><p>' . esc_html__( 'You have been unsubscribed.', 'club-events' ) . '</p></div>';
        }

        $categories = get_terms( [ 'taxonomy' => 'event_category', 'hide_empty' => false ] );

        ob_start();
        ?>
        <div class="ce-subscribe-wrap">
            <form class="ce-subscribe-form" id="ce-subscribe-form">
                <?php wp_nonce_field( 'ce_subscribe_nonce', 'ce_subscribe_nonce_field' ); ?>
                <h3 class="ce-subscribe-title"><?php esc_html_e( 'Stay Updated', 'club-events' ); ?></h3>
                <p class="ce-subscribe-desc"><?php esc_html_e( 'Subscribe to receive email notifications when new events are posted.', 'club-events' ); ?></p>

                <div class="ce-form-row">
                    <label for="ce-sub-name"><?php esc_html_e( 'Your Name', 'club-events' ); ?></label>
                    <input type="text" id="ce-sub-name" name="name" placeholder="<?php esc_attr_e( 'Optional', 'club-events' ); ?>" autocomplete="name">
                </div>

                <div class="ce-form-row">
                    <label for="ce-sub-email"><?php esc_html_e( 'Email Address', 'club-events' ); ?> <span class="required">*</span></label>
                    <input type="email" id="ce-sub-email" name="email" required autocomplete="email" placeholder="you@example.com">
                </div>

                <?php if ( ! is_wp_error( $categories ) && count( $categories ) > 1 ) : ?>
                <div class="ce-form-row">
                    <label><?php esc_html_e( 'Categories (optional)', 'club-events' ); ?></label>
                    <div class="ce-cat-checkboxes">
                        <?php foreach ( $categories as $cat ) : ?>
                        <label class="ce-checkbox-label">
                            <input type="checkbox" name="categories[]" value="<?php echo esc_attr( $cat->slug ); ?>">
                            <?php echo esc_html( $cat->name ); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="ce-form-row">
                    <button type="submit" class="ce-btn ce-btn-primary ce-btn-full" id="ce-subscribe-btn">
                        <?php esc_html_e( 'Subscribe', 'club-events' ); ?>
                    </button>
                </div>

                <div id="ce-subscribe-msg" class="ce-form-msg" hidden></div>
            </form>
        </div>
        <script>
        (function(){
            var form = document.getElementById('ce-subscribe-form');
            if (!form) return;
            form.addEventListener('submit', function(e){
                e.preventDefault();
                var btn = document.getElementById('ce-subscribe-btn');
                var msg = document.getElementById('ce-subscribe-msg');
                btn.disabled = true;
                btn.textContent = '<?php echo esc_js( __( 'Subscribing…', 'club-events' ) ); ?>';

                var data = new FormData(form);
                var cats = Array.from(form.querySelectorAll('[name="categories[]"]:checked')).map(function(c){return c.value;});
                data.set('categories', cats.join(','));
                data.set('action', 'ce_subscribe');
                data.set('nonce', form.querySelector('[name="ce_subscribe_nonce_field"]').value);

                fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                    method: 'POST', body: data
                }).then(function(r){return r.json();}).then(function(res){
                    msg.hidden = false;
                    msg.className = 'ce-form-msg ' + (res.success ? 'ce-form-msg--success' : 'ce-form-msg--error');
                    msg.textContent = res.data;
                    if (res.success) {
                        form.reset();
                        btn.textContent = '<?php echo esc_js( __( 'Subscribed!', 'club-events' ) ); ?>';
                    } else {
                        btn.disabled = false;
                        btn.textContent = '<?php echo esc_js( __( 'Subscribe', 'club-events' ) ); ?>';
                    }
                }).catch(function(){
                    btn.disabled = false;
                    btn.textContent = '<?php echo esc_js( __( 'Subscribe', 'club-events' ) ); ?>';
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}
