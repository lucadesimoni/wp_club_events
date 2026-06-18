<?php
defined( 'ABSPATH' ) || exit;

class CE_Elementor {

    public function __construct() {
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
        add_action( 'elementor/elements/categories_registered', [ $this, 'register_categories' ] );
        add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'frontend_styles' ] );
    }

    public function register_categories( $elements_manager ) {
        $elements_manager->add_category( 'club-events', [
            'title' => __( 'Club Events', 'club-events' ),
            'icon'  => 'eicon-calendar',
        ] );
    }

    public function register_widgets( $widgets_manager ) {
        $widgets_manager->register( new CE_Elementor_Timeline() );
        $widgets_manager->register( new CE_Elementor_Overview() );
        $widgets_manager->register( new CE_Elementor_Cards() );
        $widgets_manager->register( new CE_Elementor_List() );
        $widgets_manager->register( new CE_Elementor_Yearly() );
        $widgets_manager->register( new CE_Elementor_Subscribe() );
    }

    public function frontend_styles() {
        wp_enqueue_style( 'club-events' );
        wp_enqueue_script( 'club-events' );
    }
}

/* ─── Shared helpers for widget controls ──────────────────────────────── */
trait CE_Elementor_Controls {

    protected function add_content_controls( array $opts = [] ) {
        $this->start_controls_section( 'section_content', [
            'label' => __( 'Content', 'club-events' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'category', [
            'label'       => __( 'Category Slug', 'club-events' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'description' => __( 'Filter by event category slug. Leave empty for all.', 'club-events' ),
        ] );

        $this->add_control( 'event_type', [
            'label'       => __( 'Event Type Slug', 'club-events' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'description' => __( 'Filter by event type slug. Leave empty for all.', 'club-events' ),
        ] );

        if ( ! empty( $opts['filter_by'] ) ) {
            $this->add_control( 'filter_by', [
                'label'   => __( 'Filter Bar Shows', 'club-events' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'category',
                'options' => [
                    'category'   => __( 'Categories', 'club-events' ),
                    'event_type' => __( 'Event Types', 'club-events' ),
                ],
            ] );
        }

        if ( ! empty( $opts['limit'] ) ) {
            $this->add_control( 'limit', [
                'label'   => __( 'Max Events', 'club-events' ),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'default' => $opts['limit'],
                'min'     => 1,
                'max'     => 200,
            ] );
        }

        if ( ! empty( $opts['columns'] ) ) {
            $this->add_control( 'columns', [
                'label'   => __( 'Columns', 'club-events' ),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'default' => 3,
                'min'     => 1,
                'max'     => 4,
            ] );
        }

        if ( ! empty( $opts['show_past'] ) ) {
            $this->add_control( 'show_past', [
                'label'        => __( 'Show Past Events', 'club-events' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
            ] );
        }

        if ( ! empty( $opts['show_filter'] ) ) {
            $this->add_control( 'show_filter', [
                'label'        => __( 'Show Filter Bar', 'club-events' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ] );
        }

        if ( ! empty( $opts['show_image'] ) ) {
            $this->add_control( 'show_image', [
                'label'        => __( 'Show Image', 'club-events' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ] );
        }

        $this->end_controls_section();
    }

    protected function build_shortcode( string $tag, array $keys ): string {
        $s    = $this->get_settings_for_display();
        $atts = '';
        foreach ( $keys as $key ) {
            $val = $s[ $key ] ?? '';
            if ( in_array( $key, [ 'show_past', 'show_filter', 'show_image' ], true ) ) {
                $val = ( $val === 'yes' ) ? '1' : '0';
            }
            if ( '' !== $val ) {
                $atts .= ' ' . $key . '="' . esc_attr( $val ) . '"';
            }
        }
        return do_shortcode( '[' . $tag . $atts . ']' );
    }
}

/* ═══════════════════════════════════════════════════════════════════════ */
/*  Widget: Events Timeline                                              */
/* ═══════════════════════════════════════════════════════════════════════ */
class CE_Elementor_Timeline extends \Elementor\Widget_Base {
    use CE_Elementor_Controls;

    public function get_name()       { return 'club-events-timeline'; }
    public function get_title()      { return __( 'Events Timeline', 'club-events' ); }
    public function get_icon()       { return 'eicon-time-line'; }
    public function get_categories() { return [ 'club-events' ]; }
    public function get_keywords()   { return [ 'events', 'timeline', 'calendar', 'club' ]; }

    protected function register_controls() {
        $this->add_content_controls( [
            'filter_by'   => true,
            'limit'       => 20,
            'show_past'   => true,
            'show_filter' => true,
        ] );
    }

    protected function render() {
        echo $this->build_shortcode( 'club_events_timeline', [
            'category', 'event_type', 'filter_by', 'limit', 'show_past', 'show_filter',
        ] );
    }
}

/* ═══════════════════════════════════════════════════════════════════════ */
/*  Widget: Events Calendar / Overview                                   */
/* ═══════════════════════════════════════════════════════════════════════ */
class CE_Elementor_Overview extends \Elementor\Widget_Base {
    use CE_Elementor_Controls;

    public function get_name()       { return 'club-events-overview'; }
    public function get_title()      { return __( 'Events Calendar', 'club-events' ); }
    public function get_icon()       { return 'eicon-calendar'; }
    public function get_categories() { return [ 'club-events' ]; }
    public function get_keywords()   { return [ 'events', 'calendar', 'overview', 'monthly' ]; }

    protected function register_controls() {
        $this->add_content_controls( [
            'filter_by'   => true,
            'show_filter' => true,
        ] );
    }

    protected function render() {
        echo $this->build_shortcode( 'club_events_overview', [
            'category', 'event_type', 'filter_by', 'show_filter',
        ] );
    }
}

/* ═══════════════════════════════════════════════════════════════════════ */
/*  Widget: Events Cards                                                 */
/* ═══════════════════════════════════════════════════════════════════════ */
class CE_Elementor_Cards extends \Elementor\Widget_Base {
    use CE_Elementor_Controls;

    public function get_name()       { return 'club-events-cards'; }
    public function get_title()      { return __( 'Events Cards', 'club-events' ); }
    public function get_icon()       { return 'eicon-posts-grid'; }
    public function get_categories() { return [ 'club-events' ]; }
    public function get_keywords()   { return [ 'events', 'cards', 'grid', 'club' ]; }

    protected function register_controls() {
        $this->add_content_controls( [
            'filter_by'   => true,
            'limit'       => 6,
            'columns'     => true,
            'show_past'   => true,
            'show_filter' => true,
            'show_image'  => true,
        ] );
    }

    protected function render() {
        echo $this->build_shortcode( 'club_events_cards', [
            'category', 'event_type', 'filter_by', 'limit', 'columns',
            'show_past', 'show_filter', 'show_image',
        ] );
    }
}

/* ═══════════════════════════════════════════════════════════════════════ */
/*  Widget: Events List                                                  */
/* ═══════════════════════════════════════════════════════════════════════ */
class CE_Elementor_List extends \Elementor\Widget_Base {
    use CE_Elementor_Controls;

    public function get_name()       { return 'club-events-list'; }
    public function get_title()      { return __( 'Events List', 'club-events' ); }
    public function get_icon()       { return 'eicon-editor-list-ul'; }
    public function get_categories() { return [ 'club-events' ]; }
    public function get_keywords()   { return [ 'events', 'list', 'upcoming', 'club' ]; }

    protected function register_controls() {
        $this->add_content_controls( [
            'limit'     => 5,
            'show_past' => true,
        ] );
    }

    protected function render() {
        echo $this->build_shortcode( 'club_events_list', [
            'category', 'event_type', 'limit', 'show_past',
        ] );
    }
}

/* ═══════════════════════════════════════════════════════════════════════ */
/*  Widget: Yearly Agenda                                                */
/* ═══════════════════════════════════════════════════════════════════════ */
class CE_Elementor_Yearly extends \Elementor\Widget_Base {
    use CE_Elementor_Controls;

    public function get_name()       { return 'club-events-yearly'; }
    public function get_title()      { return __( 'Yearly Agenda', 'club-events' ); }
    public function get_icon()       { return 'eicon-date'; }
    public function get_categories() { return [ 'club-events' ]; }
    public function get_keywords()   { return [ 'events', 'yearly', 'agenda', 'annual' ]; }

    protected function register_controls() {
        $this->start_controls_section( 'section_content', [
            'label' => __( 'Content', 'club-events' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'category', [
            'label'   => __( 'Category Slug', 'club-events' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ] );

        $this->add_control( 'event_type', [
            'label'   => __( 'Event Type Slug', 'club-events' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ] );

        $this->add_control( 'year', [
            'label'   => __( 'Year (0 = current)', 'club-events' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 0,
            'min'     => 0,
            'max'     => 2099,
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        echo $this->build_shortcode( 'club_events_yearly', [
            'category', 'event_type', 'year',
        ] );
    }
}

/* ═══════════════════════════════════════════════════════════════════════ */
/*  Widget: Events Subscribe Form                                        */
/* ═══════════════════════════════════════════════════════════════════════ */
class CE_Elementor_Subscribe extends \Elementor\Widget_Base {

    public function get_name()       { return 'club-events-subscribe'; }
    public function get_title()      { return __( 'Events Subscribe Form', 'club-events' ); }
    public function get_icon()       { return 'eicon-email-field'; }
    public function get_categories() { return [ 'club-events' ]; }
    public function get_keywords()   { return [ 'events', 'subscribe', 'email', 'newsletter' ]; }

    protected function register_controls() {}

    protected function render() {
        echo do_shortcode( '[club_events_subscribe]' );
    }
}
