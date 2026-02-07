<?php
/**
 * Active Demos Page View
 *
 * @package DemoWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap demowp-admin-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-list-view"></span>
        <?php esc_html_e( 'Active Demos', 'demowp' ); ?>
    </h1>

    <a href="<?php echo esc_url( DemoWP_Public::get_endpoint_url() ); ?>" class="page-title-action" target="_blank">
        <?php esc_html_e( 'Create New Demo', 'demowp' ); ?>
    </a>
    <?php if ( ! empty( $demos ) ) : ?>
        <button type="button" class="page-title-action demowp-cleanup-all-btn" id="demowp-cleanup-all">
            <?php esc_html_e( 'Delete All Demos', 'demowp' ); ?>
        </button>
    <?php endif; ?>

    <hr class="wp-header-end">

    <div class="demowp-admin-content">
        <?php if ( empty( $demos ) ) : ?>
            <div class="demowp-card demowp-empty-state">
                <span class="dashicons dashicons-welcome-view-site"></span>
                <h2><?php esc_html_e( 'No Active Demos', 'demowp' ); ?></h2>
                <p><?php esc_html_e( 'There are no active demos at the moment.', 'demowp' ); ?></p>
                <a href="<?php echo esc_url( DemoWP_Public::get_endpoint_url() ); ?>" class="button button-primary" target="_blank">
                    <?php esc_html_e( 'Create a Demo', 'demowp' ); ?>
                </a>
            </div>
        <?php else : ?>
            <div class="demowp-card">
                <table class="wp-list-table widefat fixed striped demowp-demos-table">
                    <thead>
                        <tr>
                            <th scope="col" class="column-clone-id">
                                <?php esc_html_e( 'Clone ID', 'demowp' ); ?>
                            </th>
                            <th scope="col" class="column-username">
                                <?php esc_html_e( 'Username', 'demowp' ); ?>
                            </th>
                            <th scope="col" class="column-ip">
                                <?php esc_html_e( 'IP Address', 'demowp' ); ?>
                            </th>
                            <th scope="col" class="column-created">
                                <?php esc_html_e( 'Created', 'demowp' ); ?>
                            </th>
                            <th scope="col" class="column-expires">
                                <?php esc_html_e( 'Expires In', 'demowp' ); ?>
                            </th>
                            <th scope="col" class="column-actions">
                                <?php esc_html_e( 'Actions', 'demowp' ); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $demos as $demo ) : ?>
                            <?php
                            $clone_url      = home_url( $demo['clone_id'] . '/' );
                            $expires_at     = strtotime( $demo['expires_at'] );
                            $time_remaining = DemoWP_Utils::get_time_remaining( $expires_at );
                            $is_expired     = $expires_at < time();
                            $is_blocked     = isset( $demo['blocked'] ) && 1 === (int) $demo['blocked'];
                            ?>
                            <tr data-clone-id="<?php echo esc_attr( $demo['clone_id'] ); ?>">
                                <td class="column-clone-id">
                                    <strong>
                                        <a href="<?php echo esc_url( $clone_url ); ?>" target="_blank">
                                            <?php echo esc_html( substr( $demo['clone_id'], 0, 16 ) . '...' ); ?>
                                        </a>
                                    </strong>
                                    <br>
                                    <span class="demowp-prefix">
                                        <?php echo esc_html( $demo['db_prefix'] ); ?>
                                    </span>
                                </td>
                                <td class="column-username">
                                    <code><?php echo esc_html( $demo['username'] ); ?></code>
                                </td>
                                <td class="column-ip">
                                    <?php echo esc_html( $demo['ip_address'] ?: '—' ); ?>
                                </td>
                                <td class="column-created">
                                    <?php
                                    echo esc_html(
                                        sprintf(
                                            /* translators: %s: time ago */
                                            __( '%s ago', 'demowp' ),
                                            human_time_diff( strtotime( $demo['created_at'] ) )
                                        )
                                    );
                                    ?>
                                    <br>
                                    <span class="demowp-date">
                                        <?php echo esc_html( gmdate( 'M j, H:i', strtotime( $demo['created_at'] ) ) ); ?>
                                    </span>
                                </td>
                                <td class="column-expires">
                                    <?php if ( $is_blocked ) : ?>
                                        <span class="demowp-badge demowp-badge-blocked">
                                            <?php esc_html_e( 'Blocked', 'demowp' ); ?>
                                        </span>
                                    <?php elseif ( $is_expired ) : ?>
                                        <span class="demowp-badge demowp-badge-expired">
                                            <?php esc_html_e( 'Expired', 'demowp' ); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="demowp-badge demowp-badge-active">
                                            <?php echo esc_html( $time_remaining ); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
								<td class="column-actions">
									<a href="<?php echo esc_url( $clone_url . 'wp-admin/' ); ?>" class="button button-small" target="_blank">
										<?php esc_html_e( 'Admin', 'demowp' ); ?>
									</a>
									<?php if ( $is_blocked ) : ?>
										<button type="button" class="button button-small demowp-unblock-demo" data-clone-id="<?php echo esc_attr( $demo['clone_id'] ); ?>">
											<?php esc_html_e( 'Unblock', 'demowp' ); ?>
										</button>
									<?php else : ?>
										<button type="button" class="button button-small demowp-block-demo" data-clone-id="<?php echo esc_attr( $demo['clone_id'] ); ?>">
											<?php esc_html_e( 'Block', 'demowp' ); ?>
										</button>
									<?php endif; ?>
									<button type="button" class="button button-small button-link-delete demowp-delete-demo" data-clone-id="<?php echo esc_attr( $demo['clone_id'] ); ?>">
										<?php esc_html_e( 'Delete', 'demowp' ); ?>
									</button>
								</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="demowp-table-footer">
                    <?php
                    printf(
                        /* translators: %d: number of active demos */
                        esc_html( _n( '%d active demo', '%d active demos', count( $demos ), 'demowp' ) ),
                        count( $demos )
                    );
                    ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>
