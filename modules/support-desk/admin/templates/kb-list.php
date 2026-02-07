<?php
/**
 * Knowledge Base articles list admin template.
 *
 * @package HostForge\Modules\SupportDesk\Admin
 * @var array $articles   Array of WP_Post KB articles.
 * @var array $categories Array of WP_Term KB categories.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Knowledge Base', 'hostforge' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-knowledge-base&action=new' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New Article', 'hostforge' ); ?>
	</a>
	<hr class="wp-header-end">

	<div id="hf-kb-notice" class="notice" style="display:none;"><p></p></div>

	<?php if ( ! empty( $articles ) ) : ?>
		<table class="widefat striped hf-kb-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Category', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Visibility', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Votes', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Status', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Date', 'hostforge' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'hostforge' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $articles as $article ) : ?>
					<?php
					$visibility_raw = get_post_meta( $article->ID, '_hf_visibility', true );
					$visibility     = ! empty( $visibility_raw ) ? $visibility_raw : 'public';
					$helpful_yes    = absint( get_post_meta( $article->ID, '_hf_helpful_yes', true ) );
					$helpful_no     = absint( get_post_meta( $article->ID, '_hf_helpful_no', true ) );
					$article_cats   = wp_get_object_terms( $article->ID, 'hf_kb_category', array( 'fields' => 'names' ) );
					$edit_url       = admin_url( 'admin.php?page=hostforge-knowledge-base&action=edit&article_id=' . $article->ID );

					$visibility_labels = array(
						'public'         => __( 'Public', 'hostforge' ),
						'logged_in'      => __( 'Logged In', 'hostforge' ),
						'customers_only' => __( 'Customers Only', 'hostforge' ),
					);
					$visibility_label  = $visibility_labels[ $visibility ] ?? ucfirst( $visibility );
					?>
					<tr data-article-id="<?php echo esc_attr( $article->ID ); ?>">
						<td>
							<strong>
								<a href="<?php echo esc_url( $edit_url ); ?>">
									<?php echo esc_html( $article->post_title ); ?>
								</a>
							</strong>
						</td>
						<td>
							<?php
							if ( ! is_wp_error( $article_cats ) && ! empty( $article_cats ) ) {
								echo esc_html( implode( ', ', $article_cats ) );
							} else {
								echo '<span class="hf-muted">' . esc_html__( 'None', 'hostforge' ) . '</span>';
							}
							?>
						</td>
						<td>
							<span class="hf-badge hf-badge--<?php echo esc_attr( $visibility ); ?>">
								<?php echo esc_html( $visibility_label ); ?>
							</span>
						</td>
						<td>
							<span class="hf-votes">
								<span class="hf-votes__yes" title="<?php esc_attr_e( 'Helpful', 'hostforge' ); ?>">
									<?php echo esc_html( $helpful_yes ); ?>
								</span>
								/
								<span class="hf-votes__no" title="<?php esc_attr_e( 'Not helpful', 'hostforge' ); ?>">
									<?php echo esc_html( $helpful_no ); ?>
								</span>
							</span>
						</td>
						<td>
							<?php
							$post_status_labels = array(
								'publish' => __( 'Published', 'hostforge' ),
								'draft'   => __( 'Draft', 'hostforge' ),
								'pending' => __( 'Pending', 'hostforge' ),
							);
							$post_status_label  = $post_status_labels[ $article->post_status ] ?? ucfirst( $article->post_status );
							?>
							<span class="hf-badge hf-badge--<?php echo esc_attr( $article->post_status ); ?>">
								<?php echo esc_html( $post_status_label ); ?>
							</span>
						</td>
						<td>
							<?php echo esc_html( get_the_date( get_option( 'date_format' ), $article ) ); ?>
						</td>
						<td>
							<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small">
								<?php esc_html_e( 'Edit', 'hostforge' ); ?>
							</a>
							<button type="button" class="button button-small button-link-delete hf-delete-kb-article"
								data-id="<?php echo esc_attr( $article->ID ); ?>">
								<?php esc_html_e( 'Delete', 'hostforge' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<div class="hf-card">
			<p class="hf-muted"><?php esc_html_e( 'No knowledge base articles found. Click "Add New Article" to create one.', 'hostforge' ); ?></p>
		</div>
	<?php endif; ?>
</div>
