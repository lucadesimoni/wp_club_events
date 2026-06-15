<?php
defined( 'ABSPATH' ) || exit;

class CE_ICS_Export {

    public function __construct() {
        add_action( 'init', [ $this, 'register_rewrite_rules' ] );
        add_action( 'template_redirect', [ $this, 'handle_feed' ] );
        add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
    }

    public function register_rewrite_rules() {
        add_rewrite_rule( '^events\.ics$', 'index.php?ce_ics_feed=1', 'top' );
        add_rewrite_rule( '^events/([0-9]+)\.ics$', 'index.php?ce_ics_single=$matches[1]', 'top' );
    }

    public function add_query_vars( $vars ) {
        $vars[] = 'ce_ics_feed';
        $vars[] = 'ce_ics_single';
        return $vars;
    }

    public function handle_feed() {
        if ( get_option( 'ce_ics_feed_enabled', '1' ) !== '1' ) {
            return;
        }

        if ( get_query_var( 'ce_ics_feed' ) ) {
            $category = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : '';
            $this->output_feed( $category );
            exit;
        }

        $single_id = (int) get_query_var( 'ce_ics_single' );
        if ( $single_id ) {
            $this->output_single( $single_id );
            exit;
        }
    }

    private function output_feed( $category = '' ) {
        $args = [
            'from' => date( 'Y-m-d H:i:s', strtotime( '-1 month' ) ),
            'to'   => date( 'Y-m-d H:i:s', strtotime( '+12 months' ) ),
        ];

        if ( $category ) {
            $args['tax_query'] = [ [
                'taxonomy' => 'event_category',
                'field'    => 'slug',
                'terms'    => $category,
            ] ];
        }

        $posts = CE_CPT::get_events( $args );

        $site_name = get_bloginfo( 'name' );
        $events    = array_map( fn( $p ) => CE_CPT::format_event( $p->ID ), $posts );

        header( 'Content-Type: text/calendar; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="events.ics"' );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );

        echo $this->build_ics( $events, $site_name . ' — ' . __( 'Events', 'club-events' ) );
    }

    private function output_single( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || 'club_event' !== $post->post_type || 'publish' !== $post->post_status ) {
            wp_die( esc_html__( 'Event not found.', 'club-events' ), 404 );
        }

        $event = CE_CPT::format_event( $post_id );

        header( 'Content-Type: text/calendar; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="event-' . $post_id . '.ics"' );

        echo $this->build_ics( [ $event ], $event['title'] );
    }

    private function build_ics( array $events, $cal_name ) {
        $lines   = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//Club Events Manager//WordPress//EN';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';
        $lines[] = $this->fold_property( 'X-WR-CALNAME', $this->ics_escape( $cal_name ) );
        $lines[] = 'X-WR-TIMEZONE:' . get_option( 'timezone_string', 'UTC' );

        foreach ( $events as $event ) {
            $uid = get_post_meta( $event['id'], '_ce_ical_uid', true );
            if ( ! $uid ) {
                $uid = 'ce-' . $event['id'] . '@' . parse_url( home_url(), PHP_URL_HOST );
            }

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = $this->fold_property( 'UID', $uid );
            $lines[] = $this->fold_property( 'SUMMARY', $this->ics_escape( $event['title'] ) );

            if ( $event['allDay'] ) {
                $lines[] = 'DTSTART;VALUE=DATE:' . date( 'Ymd', strtotime( $event['start'] ) );
                if ( $event['end'] ) {
                    $lines[] = 'DTEND;VALUE=DATE:' . date( 'Ymd', strtotime( $event['end'] ) );
                }
            } else {
                $tz = get_option( 'timezone_string', 'UTC' );
                $lines[] = 'DTSTART;TZID=' . $tz . ':' . date( 'Ymd\THis', strtotime( $event['start'] ) );
                if ( $event['end'] ) {
                    $lines[] = 'DTEND;TZID=' . $tz . ':' . date( 'Ymd\THis', strtotime( $event['end'] ) );
                }
            }

            if ( $event['location'] ) {
                $lines[] = $this->fold_property( 'LOCATION', $this->ics_escape( $event['location'] ) );
            }

            if ( $event['excerpt'] ) {
                $lines[] = $this->fold_property( 'DESCRIPTION', $this->ics_escape( $event['excerpt'] ) );
            }

            $lines[] = $this->fold_property( 'URL', $event['url'] );
            $lines[] = 'DTSTAMP:' . gmdate( 'Ymd\THis\Z' );
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode( "\r\n", $lines ) . "\r\n";
    }

    private function ics_escape( $str ) {
        $str = strip_tags( html_entity_decode( $str, ENT_QUOTES, 'UTF-8' ) );
        $str = str_replace( [ '\\', ';', ',' ], [ '\\\\', '\;', '\,' ], $str );
        $str = str_replace( "\n", '\n', $str );
        return $str;
    }

    private function fold_property( $name, $value ) {
        return $this->fold_line( $name . ':' . $value );
    }

    private function fold_line( $str ) {
        $result = '';
        $length = 0;
        foreach ( mb_str_split( $str ) as $char ) {
            $char_bytes = strlen( $char );
            if ( $length + $char_bytes > 75 ) {
                $result .= "\r\n ";
                $length  = 1;
            }
            $result .= $char;
            $length += $char_bytes;
        }
        return $result;
    }

    public static function get_feed_url( $category = '' ) {
        $url = home_url( '/events.ics' );
        if ( $category ) {
            $url = add_query_arg( 'category', urlencode( $category ), $url );
        }
        return $url;
    }

    public static function get_single_url( $post_id ) {
        return home_url( '/events/' . $post_id . '.ics' );
    }
}
