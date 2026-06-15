<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap ce-admin-wrap">
    <h1 class="ce-page-title">
        <span class="dashicons dashicons-email-alt"></span>
        <?php esc_html_e( 'Subscribers', 'club-events' ); ?>
        <span class="ce-count-badge"><?php echo esc_html( $result['total'] ); ?></span>
    </h1>

    <div class="ce-card">
        <div class="ce-card-header">
            <h2><?php esc_html_e( 'Email Subscribers', 'club-events' ); ?></h2>
            <span class="ce-hint">
                <?php printf( esc_html__( '%d confirmed subscribers', 'club-events' ), esc_html( $result['total'] ) ); ?>
            </span>
        </div>

        <?php if ( empty( $result['rows'] ) ) : ?>
        <div class="ce-empty-state ce-empty-state--centered">
            <span class="dashicons dashicons-email-alt ce-empty-icon"></span>
            <p><?php esc_html_e( 'No subscribers yet.', 'club-events' ); ?></p>
            <p class="ce-hint">
                <?php esc_html_e( 'Add the subscription form to a page with', 'club-events' ); ?>
                <code>[club_events_subscribe]</code>
            </p>
        </div>
        <?php else : ?>
        <table class="wp-list-table widefat fixed striped ce-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Email', 'club-events' ); ?></th>
                    <th><?php esc_html_e( 'Name', 'club-events' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'club-events' ); ?></th>
                    <th><?php esc_html_e( 'Categories', 'club-events' ); ?></th>
                    <th><?php esc_html_e( 'Subscribed', 'club-events' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'club-events' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $result['rows'] as $sub ) : ?>
                <tr data-id="<?php echo esc_attr( $sub->id ); ?>">
                    <td><strong><?php echo esc_html( $sub->email ); ?></strong></td>
                    <td><?php echo esc_html( $sub->name ?: '—' ); ?></td>
                    <td>
                        <?php if ( $sub->confirmed ) : ?>
                        <span class="ce-badge ce-badge--green"><?php esc_html_e( 'Confirmed', 'club-events' ); ?></span>
                        <?php else : ?>
                        <span class="ce-badge ce-badge--yellow"><?php esc_html_e( 'Pending', 'club-events' ); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $sub->categories ? esc_html( $sub->categories ) : '<span class="ce-hint">' . esc_html__( 'All', 'club-events' ) . '</span>'; ?></td>
                    <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $sub->created_at ) ) ); ?></td>
                    <td>
                        <button class="button button-small ce-delete-sub-btn" data-id="<?php echo esc_attr( $sub->id ); ?>">
                            <?php esc_html_e( 'Delete', 'club-events' ); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ( $result['total'] > 50 ) : ?>
        <div class="ce-pagination">
            <?php
            $total_pages = ceil( $result['total'] / 50 );
            for ( $i = 1; $i <= $total_pages; $i++ ) {
                $url = add_query_arg( [ 'page' => 'ce-subscribers', 'paged' => $i ], admin_url( 'admin.php' ) );
                printf(
                    '<a href="%s" class="button%s">%d</a> ',
                    esc_url( $url ),
                    $page === $i ? ' button-primary' : '',
                    $i
                );
            }
            ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="ce-card">
        <div class="ce-card-header">
            <h2><?php esc_html_e( 'Subscription Form', 'club-events' ); ?></h2>
        </div>
        <p>
            <?php esc_html_e( 'Add the subscription form to any page or post using:', 'club-events' ); ?>
            <code>[club_events_subscribe]</code>
        </p>
        <p>
            <?php esc_html_e( 'Or place it in a template with PHP:', 'club-events' ); ?>
            <code>&lt;?php echo do_shortcode( \'[club_events_subscribe]\' ); ?&gt;</code>
        </p>
    </div>
</div>
