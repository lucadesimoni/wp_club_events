<?php
defined( 'ABSPATH' ) || exit;

class CE_REST_API {

    const NAMESPACE = 'club-events/v1';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( self::NAMESPACE, '/events', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_events' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'from'     => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
                'to'       => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
                'category' => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
                'limit'    => [ 'type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 200 ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/events/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_event' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( self::NAMESPACE, '/categories', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_categories' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function get_events( WP_REST_Request $request ) {
        $args = [
            'posts_per_page' => $request->get_param( 'limit' ),
        ];

        $from = $request->get_param( 'from' );
        $to   = $request->get_param( 'to' );

        if ( $from ) {
            $args['from'] = date( 'Y-m-d H:i:s', strtotime( $from ) );
        }
        if ( $to ) {
            $args['to'] = date( 'Y-m-d H:i:s', strtotime( $to ) );
        }

        $category = $request->get_param( 'category' );
        if ( $category ) {
            $args['tax_query'] = [ [
                'taxonomy' => 'event_category',
                'field'    => 'slug',
                'terms'    => $category,
            ] ];
        }

        $posts  = CE_CPT::get_events( $args );
        $events = array_map( fn( $p ) => CE_CPT::format_event( $p->ID ), $posts );

        return rest_ensure_response( $events );
    }

    public function get_event( WP_REST_Request $request ) {
        $id   = (int) $request->get_param( 'id' );
        $post = get_post( $id );

        if ( ! $post || 'club_event' !== $post->post_type || 'publish' !== $post->post_status ) {
            return new WP_Error( 'not_found', __( 'Event not found.', 'club-events' ), [ 'status' => 404 ] );
        }

        return rest_ensure_response( CE_CPT::format_event( $id ) );
    }

    public function get_categories( WP_REST_Request $request ) {
        $terms = get_terms( [
            'taxonomy'   => 'event_category',
            'hide_empty' => true,
        ] );

        if ( is_wp_error( $terms ) ) {
            return rest_ensure_response( [] );
        }

        $data = array_map( fn( $t ) => [
            'id'    => $t->term_id,
            'name'  => $t->name,
            'slug'  => $t->slug,
            'count' => $t->count,
        ], $terms );

        return rest_ensure_response( $data );
    }
}
