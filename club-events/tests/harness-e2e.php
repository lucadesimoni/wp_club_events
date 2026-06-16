<?php
/**
 * E2E test harness — WordPress stub environment for Club Events Manager
 *
 * Provides: WordPress function stubs, stub classes (WP_Error, WP_REST_*),
 * the E2E_WP state tracker, a CE_CPT stub, and auto-runs the Aktivriege
 * import so section tests can inspect E2E_WP::$created_posts immediately.
 */

// ── Constants ─────────────────────────────────────────────────────────────────
define( 'ABSPATH', '/tmp/ce-e2e/' );
// Define WP_CLI so the import file takes the CLI path (skips add_action/return)
// and auto-calls ce_import_aktivriege_2026() on include.
define( 'WP_CLI', true );

// ── WP_CLI stub ───────────────────────────────────────────────────────────────
class WP_CLI {
    public static array $lines = [];
    public static function line( string $msg ): void { self::$lines[] = $msg; }
}

// ── Core WordPress classes ────────────────────────────────────────────────────
class WP_Error {
    private string $code;
    private string $message;
    public function __construct( string $code = '', string $message = '', $data = '' ) {
        $this->code    = $code;
        $this->message = $message;
    }
    public function get_error_message( string $code = '' ): string { return $this->message; }
    public function get_error_code(): string { return $this->code; }
}

class WP_REST_Request {
    private array $params;
    public function __construct( array $params = [] ) { $this->params = $params; }
    public function get_param( string $key ) { return $this->params[ $key ] ?? null; }
}

class WP_REST_Server {
    const READABLE = 'GET';
}

// ── E2E_WP — stateful tracker ─────────────────────────────────────────────────
class E2E_WP {
    /** [post_id => ['title', 'location', 'event_types', 'category', 'meta']] */
    public static array $created_posts = [];

    /** Lines returned by ce_import_aktivriege_2026() on first run */
    public static array $import_log = [];

    /** When true, get_posts() returns existing posts (idempotency simulation) */
    public static bool $simulate_duplicates = false;

    /** [taxonomy => [slug => ['name', 'slug', 'term_id', 'count']]] */
    public static array $terms = [];

    public static int $_next_post_id = 1000;
    public static int $_next_term_id = 1;

    public static function get_term( string $taxonomy, string $slug ): ?array {
        return self::$terms[ $taxonomy ][ $slug ] ?? null;
    }
}

// ── CE_CPT stub (used by CE_REST_API) ────────────────────────────────────────
class CE_CPT {
    public static function get_events( array $args = [] ): array { return []; }

    public static function format_event( int $id ): array {
        $post = E2E_WP::$created_posts[ $id ] ?? [];
        return [
            'id'          => $id,
            'title'       => $post['title']                       ?? '',
            'start'       => $post['meta']['_ce_start_date']      ?? '',
            'end'         => $post['meta']['_ce_end_date']        ?? '',
            'allDay'      => true,
            'location'    => $post['location']                    ?? '',
            'locationUrl' => '',
            'externalUrl' => '',
            'excerpt'     => '',
            'url'         => home_url( '/events/' ),
            'color'       => $post['meta']['_ce_color']           ?? '',
            'categories'  => [],
            'types'       => [],
            'thumbnail'   => '',
        ];
    }
}

// ── WordPress function stubs ──────────────────────────────────────────────────

function add_action( $hook = '', $cb = null, $priority = 10, $args = 1 ): void {}
function add_filter( $hook = '', $cb = null, $priority = 10, $args = 1 ): void {}
function do_action( $hook = '' ): void {}
function register_rest_route(): void {}
function add_rewrite_rule(): void {}
function add_query_arg(): string { return ''; }

function is_wp_error( $thing ): bool { return $thing instanceof WP_Error; }

function __( string $text, string $domain = '' ): string { return $text; }
function esc_html( string $text ): string {
    return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}
function esc_html__( string $text, string $domain = '' ): string {
    return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}
function sanitize_text_field( string $str ): string {
    return trim( strip_tags( $str ) );
}

function term_exists( string $name, string $taxonomy = '', int $parent = 0 ) {
    if ( empty( E2E_WP::$terms[ $taxonomy ] ) ) return false;
    foreach ( E2E_WP::$terms[ $taxonomy ] as $slug => $term ) {
        if ( strcasecmp( $term['name'], $name ) === 0 ) {
            return [ 'term_id' => $term['term_id'], 'term_taxonomy_id' => $term['term_id'] ];
        }
    }
    return false;
}

function wp_insert_term( string $name, string $taxonomy, array $args = [] ) {
    $slug = $args['slug'] ?? strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $name ) );
    if ( isset( E2E_WP::$terms[ $taxonomy ][ $slug ] ) ) {
        return new WP_Error( 'term_exists', "Term '$slug' already exists in '$taxonomy'" );
    }
    $id = E2E_WP::$_next_term_id++;
    E2E_WP::$terms[ $taxonomy ][ $slug ] = [
        'name'    => $name,
        'slug'    => $slug,
        'term_id' => $id,
        'count'   => 0,
    ];
    return [ 'term_id' => $id, 'term_taxonomy_id' => $id ];
}

function get_posts( array $args = [] ): array {
    if ( ! E2E_WP::$simulate_duplicates || empty( E2E_WP::$created_posts ) ) {
        return [];
    }
    $title      = $args['title'] ?? '';
    $start_date = '';
    foreach ( $args['meta_query'] ?? [] as $mq ) {
        if ( ( $mq['key'] ?? '' ) === '_ce_start_date' ) {
            $start_date = $mq['value'];
            break;
        }
    }
    foreach ( E2E_WP::$created_posts as $id => $post ) {
        if ( $post['title'] === $title && ( $post['meta']['_ce_start_date'] ?? '' ) === $start_date ) {
            return [ $id ];
        }
    }
    return [];
}

function wp_insert_post( array $args, bool $return_wp_error = false ): int {
    $id = E2E_WP::$_next_post_id++;
    E2E_WP::$created_posts[ $id ] = [
        'title'       => $args['post_title'] ?? '',
        'location'    => '',
        'event_types' => [],
        'category'    => '',
        'meta'        => [],
    ];
    return $id;
}

function update_post_meta( int $post_id, string $key, $value ): bool {
    if ( ! isset( E2E_WP::$created_posts[ $post_id ] ) ) return false;
    E2E_WP::$created_posts[ $post_id ]['meta'][ $key ] = $value;
    if ( $key === '_ce_location' ) {
        E2E_WP::$created_posts[ $post_id ]['location'] = (string) $value;
    }
    return true;
}

function wp_set_object_terms( int $post_id, array $term_ids, string $taxonomy ): void {
    if ( ! isset( E2E_WP::$created_posts[ $post_id ] ) ) return;
    foreach ( $term_ids as $term_id ) {
        if ( empty( E2E_WP::$terms[ $taxonomy ] ) ) continue;
        foreach ( E2E_WP::$terms[ $taxonomy ] as $slug => $term ) {
            if ( (int) $term['term_id'] === (int) $term_id ) {
                if ( $taxonomy === 'event_type' ) {
                    if ( ! in_array( $slug, E2E_WP::$created_posts[ $post_id ]['event_types'], true ) ) {
                        E2E_WP::$created_posts[ $post_id ]['event_types'][] = $slug;
                    }
                } elseif ( $taxonomy === 'event_category' ) {
                    E2E_WP::$created_posts[ $post_id ]['category'] = $slug;
                }
                break;
            }
        }
    }
}

function get_terms( array $args = [] ) {
    $taxonomy = $args['taxonomy'] ?? '';
    if ( empty( E2E_WP::$terms[ $taxonomy ] ) ) return [];
    $terms = [];
    foreach ( E2E_WP::$terms[ $taxonomy ] as $slug => $data ) {
        $t          = new stdClass();
        $t->term_id = $data['term_id'];
        $t->name    = $data['name'];
        $t->slug    = $slug;
        $t->count   = $data['count'] ?? 0;
        $terms[]    = $t;
    }
    return $terms;
}

function get_option( string $key, $default = false ) {
    static $opts = [
        'timezone_string'     => 'Europe/Zurich',
        'ce_ics_feed_enabled' => '1',
    ];
    return $opts[ $key ] ?? $default;
}

function get_post_meta( int $post_id, string $key, bool $single = false ) {
    if ( $single ) return E2E_WP::$created_posts[ $post_id ]['meta'][ $key ] ?? '';
    return [];
}

function get_post( $id ) {
    $id = (int) $id;
    if ( ! isset( E2E_WP::$created_posts[ $id ] ) ) return null;
    $p              = new stdClass();
    $p->ID          = $id;
    $p->post_type   = 'club_event';
    $p->post_status = 'publish';
    $p->post_title  = E2E_WP::$created_posts[ $id ]['title'];
    return $p;
}

function home_url( string $path = '' ): string { return 'http://localhost' . $path; }
function get_bloginfo( string $show = '' ): string {
    static $info = [ 'name' => 'STV Malters', 'url' => 'http://localhost' ];
    return $info[ $show ] ?? '';
}
function get_query_var( string $key ) { return ''; }
function wp_die( string $msg = '', $title = '', $args = [] ): void {
    throw new RuntimeException( "wp_die: $msg" );
}
function rest_ensure_response( $data ) { return $data; }
function current_user_can( string $cap ): bool { return true; }

// ── Bootstrap: include import script (auto-runs via WP_CLI path) ──────────────
require_once __DIR__ . '/../tools/import-aktivriege-2026.php';

// Capture lines written to WP_CLI::line() as the canonical import log
E2E_WP::$import_log = WP_CLI::$lines;
