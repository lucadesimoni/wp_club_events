<?php
/**
 * One-time import: STV Malters — Aktivriege Jahresprogramm 2025/2026
 *
 * Usage (WP-CLI):
 *   wp eval-file wp-content/plugins/club-events/tools/import-aktivriege-2026.php
 *
 * Usage (browser, admin only):
 *   Add ?ce_run_import=aktivriege2026 to any admin URL, e.g.
 *   https://yoursite.com/wp-admin/?ce_run_import=aktivriege2026
 *
 * The script is idempotent: re-running it skips events that already exist
 * (matched by title + start date). Safe to run multiple times.
 *
 * Creates:
 *  - event_type term  : "Aktivriege"
 *  - event_category   : Vereinsanlass, Wettkampf, Training, Versammlung
 *  - 31 club_event posts from the 2025/2026 Jahresprogramm
 */

defined( 'ABSPATH' ) || exit;

/* ─── Hook for browser invocation ─────────────────────────────────────── */
if ( ! defined( 'WP_CLI' ) ) {
    add_action( 'admin_init', function () {
        if ( ! isset( $_GET['ce_run_import'] ) || $_GET['ce_run_import'] !== 'aktivriege2026' ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
        $result = ce_import_aktivriege_2026();
        wp_die( '<pre>' . esc_html( implode( "\n", $result ) ) . '</pre><p><a href="' . admin_url() . '">Back to admin</a></p>' );
    } );
    return; // rest of file only runs directly (WP-CLI) or via the hook above
}

/* ─── WP-CLI direct execution ──────────────────────────────────────────── */
$lines = ce_import_aktivriege_2026();
foreach ( $lines as $line ) {
    WP_CLI::line( $line );
}

/* ─── Import function ──────────────────────────────────────────────────── */
function ce_import_aktivriege_2026(): array {
    $log = [];

    // ── 1. Ensure event_type term "Aktivriege" exists ──────────────────
    $type_term = term_exists( 'Aktivriege', 'event_type' );
    if ( ! $type_term ) {
        $type_term = wp_insert_term( 'Aktivriege', 'event_type', [ 'slug' => 'aktivriege' ] );
    }
    if ( is_wp_error( $type_term ) ) {
        $log[] = 'ERROR creating event_type "Aktivriege": ' . $type_term->get_error_message();
        return $log;
    }
    $type_id = is_array( $type_term ) ? $type_term['term_id'] : (int) $type_term;
    $log[] = "event_type 'Aktivriege' (ID: {$type_id})";

    // ── 2. Ensure event_category terms exist ───────────────────────────
    $cats = [
        'vereinsanlass' => 'Vereinsanlass',
        'wettkampf'     => 'Wettkampf',
        'training'      => 'Training',
        'versammlung'   => 'Versammlung',
    ];
    $cat_ids = [];
    foreach ( $cats as $slug => $name ) {
        $t = term_exists( $name, 'event_category' );
        if ( ! $t ) {
            $t = wp_insert_term( $name, 'event_category', [ 'slug' => $slug ] );
        }
        if ( is_wp_error( $t ) ) {
            $log[] = "ERROR creating category '{$name}': " . $t->get_error_message();
            continue;
        }
        $cat_ids[ $slug ] = is_array( $t ) ? $t['term_id'] : (int) $t;
        $log[] = "category '{$name}' (ID: {$cat_ids[$slug]})";
    }

    // ── 3. Event data ─────────────────────────────────────────────────
    // Columns: title, start, end, all_day, location, category, color, gruppe
    // Events with unknown dates (?? / XX) are excluded.
    $events = [
        // ── November 2025 ───────────────────────────────────────────
        [
            'title'    => 'Generalversammlung',
            'start'    => '2025-11-14',
            'end'      => '2025-11-14',
            'location' => 'Malters',
            'cat'      => 'versammlung',
            'color'    => '#8b5cf6',
            'gruppe'   => 'Alle',
        ],
        // ── Dezember 2025 ───────────────────────────────────────────
        [
            'title'    => 'DV Gesamtverein',
            'start'    => '2025-12-01',
            'end'      => '2025-12-01',
            'location' => 'Malters',
            'cat'      => 'versammlung',
            'color'    => '#8b5cf6',
            'gruppe'   => 'Diverse',
        ],
        [
            'title'    => 'Weihnachtsfeier',
            'start'    => '2025-12-12',
            'end'      => '2025-12-12',
            'location' => 'Malters',
            'cat'      => 'vereinsanlass',
            'color'    => '#3b82f6',
            'gruppe'   => 'Alle',
        ],
        // ── Januar 2026 ─────────────────────────────────────────────
        [
            'title'    => 'Trainingsstart 2026',
            'start'    => '2026-01-06',
            'end'      => '2026-01-06',
            'location' => 'Malters',
            'cat'      => 'training',
            'color'    => '#22c55e',
            'gruppe'   => 'Alle',
        ],
        [
            'title'    => 'Trainingstag Malters',
            'start'    => '2026-01-17',
            'end'      => '2026-01-17',
            'location' => 'Malters',
            'cat'      => 'training',
            'color'    => '#22c55e',
            'gruppe'   => 'Alle',
        ],
        [
            'title'    => 'Pony Fatale',
            'start'    => '2026-01-31',
            'end'      => '2026-01-31',
            'location' => 'Malters',
            'cat'      => 'vereinsanlass',
            'color'    => '#3b82f6',
            'gruppe'   => 'Alle',
        ],
        // ── Februar 2026 ────────────────────────────────────────────
        [
            'title'    => 'Akjumä',
            'start'    => '2026-02-08',
            'end'      => '2026-02-08',
            'location' => 'Malters',
            'cat'      => 'wettkampf',
            'color'    => '#f97316',
            'gruppe'   => 'GETU',
            'excerpt'  => 'Interner GETU-Wettkampf (11. Durchführung) — gemischte Gruppen aus Mädchen, Knaben und Aktivriege.',
        ],
        // ── März 2026 ───────────────────────────────────────────────
        [
            'title'    => 'Skiweekend',
            'start'    => '2026-03-14',
            'end'      => '2026-03-15',
            'location' => '',
            'cat'      => 'vereinsanlass',
            'color'    => '#3b82f6',
            'gruppe'   => 'Alle ab 16 J.',
        ],
        [
            'title'    => 'Gerätemeeting K5-K7, KD & KH',
            'start'    => '2026-03-27',
            'end'      => '2026-03-28',
            'location' => 'Büron',
            'cat'      => 'wettkampf',
            'color'    => '#ef4444',
            'gruppe'   => 'GETU',
        ],
        // ── April 2026 ──────────────────────────────────────────────
        [
            'title'    => 'Trainingstag Grosswangen',
            'start'    => '2026-04-19',
            'end'      => '2026-04-19',
            'location' => 'Grosswangen',
            'cat'      => 'training',
            'color'    => '#22c55e',
            'gruppe'   => 'Alle',
        ],
        [
            'title'    => 'Vereinsturntag inkl. Präsentation Sektionen',
            'start'    => '2026-04-26',
            'end'      => '2026-04-26',
            'location' => 'Malters',
            'cat'      => 'vereinsanlass',
            'color'    => '#3b82f6',
            'gruppe'   => 'Alle',
        ],
        // ── Mai 2026 ────────────────────────────────────────────────
        [
            'title'    => 'Regionenmeisterschaften Pilatus TU K4-K7 & KH',
            'start'    => '2026-05-02',
            'end'      => '2026-05-02',
            'location' => 'Kerns',
            'cat'      => 'wettkampf',
            'color'    => '#ef4444',
            'gruppe'   => 'GETU',
        ],
        [
            'title'    => 'Regionenmeisterschaften Napf TI K4-K7 & KD',
            'start'    => '2026-05-10',
            'end'      => '2026-05-10',
            'location' => 'Dagmersellen',
            'cat'      => 'wettkampf',
            'color'    => '#ef4444',
            'gruppe'   => 'GETU',
        ],
        [
            'title'    => 'Gym-Day',
            'start'    => '2026-05-16',
            'end'      => '2026-05-16',
            'location' => 'Grosswangen',
            'cat'      => 'vereinsanlass',
            'color'    => '#3b82f6',
            'gruppe'   => 'Alle',
        ],
        [
            'title'    => 'Verbandsmeisterschaften K5-K7, KD & KH',
            'start'    => '2026-05-23',
            'end'      => '2026-05-23',
            'location' => 'Kerns',
            'cat'      => 'wettkampf',
            'color'    => '#ef4444',
            'gruppe'   => 'GETU',
        ],
        // ── Juni 2026 ───────────────────────────────────────────────
        [
            'title'    => 'GETU GAMES K4-K7, KD & KH',
            'start'    => '2026-06-06',
            'end'      => '2026-06-07',
            'location' => 'Malters',
            'cat'      => 'wettkampf',
            'color'    => '#ef4444',
            'gruppe'   => 'Alle',
        ],
        [
            'title'    => 'Turnfest Kerzers',
            'start'    => '2026-06-26',
            'end'      => '2026-06-28',
            'location' => 'Kerzers',
            'cat'      => 'vereinsanlass',
            'color'    => '#3b82f6',
            'gruppe'   => 'Alle',
        ],
        // ── Juli 2026 ───────────────────────────────────────────────
        [
            'title'    => 'Sommerabschluss',
            'start'    => '2026-07-03',
            'end'      => '2026-07-03',
            'location' => 'Malters',
            'cat'      => 'vereinsanlass',
            'color'    => '#3b82f6',
            'gruppe'   => 'Alle',
        ],
        [
            'title'    => 'Trainingsbeginn nach den Sommerferien',
            'start'    => '2026-07-28',
            'end'      => '2026-07-28',
            'location' => 'Malters',
            'cat'      => 'training',
            'color'    => '#22c55e',
            'gruppe'   => 'Alle',
        ],
        // ── September 2026 ──────────────────────────────────────────
        [
            'title'    => 'Trisa Cup TI K3-KD',
            'start'    => '2026-09-05',
            'end'      => '2026-09-06',
            'location' => 'Triengen',
            'cat'      => 'wettkampf',
            'color'    => '#ef4444',
            'gruppe'   => 'GETU',
        ],
        [
            'title'    => 'Mammut Cup K5-K7, KD & KH',
            'start'    => '2026-09-19',
            'end'      => '2026-09-19',
            'location' => 'Eschenbach',
            'cat'      => 'wettkampf',
            'color'    => '#ef4444',
            'gruppe'   => 'GETU',
        ],
        [
            'title'    => 'SM Qualifikationswettkampf K5-K7, KD & KH',
            'start'    => '2026-09-26',
            'end'      => '2026-09-26',
            'location' => 'Sarnen',
            'cat'      => 'wettkampf',
            'color'    => '#ef4444',
            'gruppe'   => 'GETU',
        ],
        // ── Oktober 2026 ────────────────────────────────────────────
        [
            'title'    => 'KIBASTRABA',
            'start'    => '2026-10-10',
            'end'      => '2026-10-10',
            'location' => 'Malters',
            'cat'      => 'vereinsanlass',
            'color'    => '#3b82f6',
            'gruppe'   => 'Alle',
            'excerpt'  => 'Kinder- und Jugendturntag / Breitensportanlass in Malters.',
        ],
        [
            'title'    => 'SM TU Einzel',
            'start'    => '2026-10-31',
            'end'      => '2026-10-31',
            'location' => 'Wankdorf, Bern',
            'cat'      => 'wettkampf',
            'color'    => '#ef4444',
            'gruppe'   => 'GETU',
            'excerpt'  => 'Schweizer Meisterschaften Turnen Einzel, Wankdorf Bern.',
        ],
        // ── November 2026 ───────────────────────────────────────────
        [
            'title'    => 'SM TU Mannschaft',
            'start'    => '2026-11-01',
            'end'      => '2026-11-01',
            'location' => 'Wankdorf, Bern',
            'cat'      => 'wettkampf',
            'color'    => '#ef4444',
            'gruppe'   => 'GETU',
            'excerpt'  => 'Schweizer Meisterschaften Turnen Mannschaft, Wankdorf Bern.',
        ],
        [
            'title'    => 'SM TI Einzel & Gerätefinal',
            'start'    => '2026-11-07',
            'end'      => '2026-11-08',
            'location' => 'Wettingen',
            'cat'      => 'wettkampf',
            'color'    => '#ef4444',
            'gruppe'   => 'GETU',
            'excerpt'  => 'Schweizer Meisterschaften Turnin Einzel & Gerätefinal, Wettingen.',
        ],
        [
            'title'    => 'SM TI Mannschaft',
            'start'    => '2026-11-14',
            'end'      => '2026-11-15',
            'location' => 'St. Gallen',
            'cat'      => 'wettkampf',
            'color'    => '#ef4444',
            'gruppe'   => 'GETU',
            'excerpt'  => 'Schweizer Meisterschaften Turnin Mannschaft, St. Gallen.',
        ],
        [
            'title'    => 'GV Aktivriege',
            'start'    => '2026-11-27',
            'end'      => '2026-11-27',
            'location' => 'Malters',
            'cat'      => 'versammlung',
            'color'    => '#8b5cf6',
            'gruppe'   => 'Alle',
        ],
        // ── Dezember 2026 ───────────────────────────────────────────
        [
            'title'    => 'DV Gesamtverein',
            'start'    => '2026-12-07',
            'end'      => '2026-12-07',
            'location' => 'Malters',
            'cat'      => 'versammlung',
            'color'    => '#8b5cf6',
            'gruppe'   => 'Diverse',
        ],
        // Turnfahrt, Turnshow, Weihnachtsfeier 2026: dates unknown — skipped.
    ];

    // ── 4. Insert events ─────────────────────────────────────────────
    $created = 0;
    $skipped = 0;

    foreach ( $events as $ev ) {
        $start_date = $ev['start'] . ' 00:00:00';
        $end_date   = $ev['end']   . ' 23:59:59';
        $all_day    = '1';

        // Skip if already exists (same title + start date)
        $existing = get_posts( [
            'post_type'   => 'club_event',
            'post_status' => 'publish',
            'title'       => $ev['title'],
            'meta_query'  => [ [
                'key'   => '_ce_start_date',
                'value' => $start_date,
            ] ],
            'numberposts' => 1,
            'fields'      => 'ids',
        ] );

        if ( ! empty( $existing ) ) {
            $log[] = "  SKIP (exists): {$ev['title']} ({$ev['start']})";
            $skipped++;
            continue;
        }

        $post_id = wp_insert_post( [
            'post_type'    => 'club_event',
            'post_status'  => 'publish',
            'post_title'   => $ev['title'],
            'post_excerpt' => $ev['excerpt'] ?? '',
            'post_content' => isset( $ev['excerpt'] ) ? '<p>' . esc_html( $ev['excerpt'] ) . '</p>' : '',
        ], true );

        if ( is_wp_error( $post_id ) ) {
            $log[] = "  ERROR inserting '{$ev['title']}': " . $post_id->get_error_message();
            continue;
        }

        update_post_meta( $post_id, '_ce_start_date', $start_date );
        update_post_meta( $post_id, '_ce_end_date',   $end_date );
        update_post_meta( $post_id, '_ce_all_day',    $all_day );
        update_post_meta( $post_id, '_ce_location',   $ev['location'] );
        update_post_meta( $post_id, '_ce_color',      $ev['color'] );
        update_post_meta( $post_id, '_ce_source',     'manual' );

        // Taxonomy: event_type → Aktivriege
        wp_set_object_terms( $post_id, [ $type_id ], 'event_type' );

        // Taxonomy: event_category
        if ( isset( $cat_ids[ $ev['cat'] ] ) ) {
            wp_set_object_terms( $post_id, [ $cat_ids[ $ev['cat'] ] ], 'event_category' );
        }

        $created++;
        $end_display = $ev['end'] !== $ev['start'] ? ' – ' . $ev['end'] : '';
        $log[] = "  ✓ Created [{$ev['gruppe']}] {$ev['title']} ({$ev['start']}{$end_display}) @ {$ev['location']}";
    }

    $log[] = '';
    $log[] = "Done. Created: {$created}  |  Skipped (already exist): {$skipped}";
    return $log;
}
