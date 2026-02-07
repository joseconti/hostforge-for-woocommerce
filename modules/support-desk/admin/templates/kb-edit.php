<?php
/**
 * Knowledge Base article edit form admin template.
 *
 * @package HostForge\Modules\SupportDesk\Admin
 * @var \WP_Post|null $article             Article post or null for new.
 * @var array         $meta                Article meta values.
 * @var array         $categories          Array of WP_Term KB categories.
 * @var array         $current_categories  Array of current category term IDs.
 */

defined( 'ABSPATH' ) || exit;

$is_edit    = ! empty( $article );
$visibility = $meta['_hf_visibility'] ?? 'public';
$related    = $meta['_hf_related_articles'] ?? array();

if ( ! is_array( $related ) ) {
	$related = array();
}

$title = $is_edit
	/* translators: %s: article title */
	? sprintf( __( 'Edit Article: %s', 'hostforge' ), $article->post_title )
	: __( 'New Article', 'hostforge' );
?>
<div class="wrap hf-wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-knowledge-base' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Back to Knowledge Base', 'hostforge' ); ?>
	</a>
	<hr class="wp-header-end">

	<div id="hf-kb-notice" class="notice" style="display:none;"><p></p></div>

	<form id="hf-kb-article-form" class="hf-form">
		<input type="hidden" name="article_id" value="<?php echo esc_attr( $is_edit ? $article->ID : 0 ); ?>" />

		<div class="hf-form-grid">
			<!-- Left Column: Main Content -->
			<div class="hf-form-col">
				<div class="hf-card">
					<h2 class="hf-card__title"><?php esc_html_e( 'Article Content', 'hostforge' ); ?></h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="hf-kb-title"><?php esc_html_e( 'Title', 'hostforge' ); ?> <span class="required">*</span></label>
							</th>
							<td>
								<input type="text" id="hf-kb-title" name="title" class="large-text" required
									value="<?php echo esc_attr( $is_edit ? $article->post_title : '' ); ?>"
									placeholder="<?php esc_attr_e( 'Enter article title...', 'hostforge' ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hf-kb-content"><?php esc_html_e( 'Content', 'hostforge' ); ?> <span class="required">*</span></label>
							</th>
							<td>
								<textarea id="hf-kb-content" name="content" rows="20" class="large-text" required
									placeholder="<?php esc_attr_e( 'Write the article content...', 'hostforge' ); ?>"><?php echo esc_textarea( $is_edit ? $article->post_content : '' ); ?></textarea>
								<p class="description"><?php esc_html_e( 'HTML is allowed. Use headings, lists, and code blocks to structure your content.', 'hostforge' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Right Column: Settings -->
			<div class="hf-form-col">
				<div class="hf-card">
					<h2 class="hf-card__title"><?php esc_html_e( 'Categories', 'hostforge' ); ?></h2>

					<?php if ( ! empty( $categories ) ) : ?>
						<div class="hf-checkbox-list">
							<?php foreach ( $categories as $cat ) : ?>
								<label class="hf-checkbox-label">
									<input type="checkbox" name="categories[]" value="<?php echo esc_attr( $cat->term_id ); ?>"
										<?php checked( in_array( $cat->term_id, $current_categories, true ) ); ?> />
									<?php echo esc_html( $cat->name ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p class="hf-muted"><?php esc_html_e( 'No categories available.', 'hostforge' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="hf-card">
					<h2 class="hf-card__title"><?php esc_html_e( 'Settings', 'hostforge' ); ?></h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="hf-kb-status"><?php esc_html_e( 'Status', 'hostforge' ); ?></label>
							</th>
							<td>
								<select id="hf-kb-status" name="status">
									<option value="publish" <?php selected( $is_edit ? $article->post_status : 'draft', 'publish' ); ?>>
										<?php esc_html_e( 'Published', 'hostforge' ); ?>
									</option>
									<option value="draft" <?php selected( $is_edit ? $article->post_status : 'draft', 'draft' ); ?>>
										<?php esc_html_e( 'Draft', 'hostforge' ); ?>
									</option>
									<option value="pending" <?php selected( $is_edit ? $article->post_status : '', 'pending' ); ?>>
										<?php esc_html_e( 'Pending Review', 'hostforge' ); ?>
									</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hf-kb-visibility"><?php esc_html_e( 'Visibility', 'hostforge' ); ?></label>
							</th>
							<td>
								<select id="hf-kb-visibility" name="visibility">
									<option value="public" <?php selected( $visibility, 'public' ); ?>>
										<?php esc_html_e( 'Public', 'hostforge' ); ?>
									</option>
									<option value="logged_in" <?php selected( $visibility, 'logged_in' ); ?>>
										<?php esc_html_e( 'Logged-in Users', 'hostforge' ); ?>
									</option>
									<option value="customers_only" <?php selected( $visibility, 'customers_only' ); ?>>
										<?php esc_html_e( 'Customers Only', 'hostforge' ); ?>
									</option>
								</select>
								<p class="description"><?php esc_html_e( 'Controls who can view this article on the frontend.', 'hostforge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="hf-kb-related"><?php esc_html_e( 'Related Articles', 'hostforge' ); ?></label>
							</th>
							<td>
								<input type="text" id="hf-kb-related" name="related_articles" class="regular-text"
									value="<?php echo esc_attr( ! empty( $related ) ? implode( ', ', array_map( 'absint', $related ) ) : '' ); ?>"
									placeholder="<?php esc_attr_e( 'e.g. 10, 25, 42', 'hostforge' ); ?>" />
								<p class="description"><?php esc_html_e( 'Comma-separated article IDs to show as related content.', 'hostforge' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<?php if ( $is_edit ) : ?>
					<div class="hf-card">
						<h2 class="hf-card__title"><?php esc_html_e( 'Stats', 'hostforge' ); ?></h2>
						<table class="hf-info-table">
							<tr>
								<th><?php esc_html_e( 'Helpful (Yes)', 'hostforge' ); ?></th>
								<td><?php echo esc_html( $meta['_hf_helpful_yes'] ?? 0 ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Not Helpful (No)', 'hostforge' ); ?></th>
								<td><?php echo esc_html( $meta['_hf_helpful_no'] ?? 0 ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Created', 'hostforge' ); ?></th>
								<td><?php echo esc_html( get_the_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $article ) ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Last Modified', 'hostforge' ); ?></th>
								<td><?php echo esc_html( get_the_modified_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $article ) ); ?></td>
							</tr>
						</table>
					</div>
				<?php endif; ?>

				<div class="hf-form-actions">
					<button type="submit" class="button button-primary button-large" id="hf-save-kb-article">
						<?php echo esc_html( $is_edit ? __( 'Update Article', 'hostforge' ) : __( 'Create Article', 'hostforge' ) ); ?>
					</button>

					<?php if ( $is_edit ) : ?>
						<button type="button" class="button button-link-delete hf-delete-kb-article" data-id="<?php echo esc_attr( $article->ID ); ?>">
							<?php esc_html_e( 'Delete Article', 'hostforge' ); ?>
						</button>
					<?php endif; ?>

					<span id="hf-kb-save-status" class="hf-inline-status"></span>
				</div>
			</div>
		</div>
	</form>
</div>
