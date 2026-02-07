<?php
/**
 * Knowledge Base — Single article view.
 *
 * Override: copy this file to theme/hostforge/frontend/kb-single.php
 *
 * @package HostForge\Templates\Frontend
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();

		$article_id       = get_the_ID();
		$yes_count        = absint( get_post_meta( $article_id, '_hf_helpful_yes', true ) );
		$no_count         = absint( get_post_meta( $article_id, '_hf_helpful_no', true ) );
		$related_ids      = get_post_meta( $article_id, '_hf_related_articles', true );
		$vote_nonce       = wp_create_nonce( 'hf_kb_vote_nonce' );

		// Category breadcrumbs.
		$article_categories = wp_get_object_terms( $article_id, 'hf_kb_category' );
		if ( is_wp_error( $article_categories ) ) {
			$article_categories = array();
		}
?>

<div class="hf-kb-single">
	<!-- Breadcrumbs -->
	<nav class="hf-kb-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumbs', 'hostforge' ); ?>">
		<a href="<?php echo esc_url( get_post_type_archive_link( 'hf_kb_article' ) ); ?>">
			<?php esc_html_e( 'Knowledge Base', 'hostforge' ); ?>
		</a>
		<?php if ( ! empty( $article_categories ) ) : ?>
			<span class="hf-kb-breadcrumbs__separator">/</span>
			<a href="<?php echo esc_url( get_term_link( $article_categories[0] ) ); ?>">
				<?php echo esc_html( $article_categories[0]->name ); ?>
			</a>
		<?php endif; ?>
		<span class="hf-kb-breadcrumbs__separator">/</span>
		<span class="hf-kb-breadcrumbs__current"><?php the_title(); ?></span>
	</nav>

	<!-- Article Header -->
	<header class="hf-kb-single__header">
		<h1 class="hf-kb-single__title"><?php the_title(); ?></h1>
		<div class="hf-kb-single__meta">
			<span class="hf-kb-single__date">
				<?php
				printf(
					/* translators: %s: article publish date */
					esc_html__( 'Last updated: %s', 'hostforge' ),
					esc_html( get_the_modified_date() )
				);
				?>
			</span>
			<?php if ( ! empty( $article_categories ) ) : ?>
				<span class="hf-kb-single__categories">
					<?php foreach ( $article_categories as $index => $cat ) :
						if ( $index > 0 ) {
							echo esc_html( ', ' );
						}
					?>
						<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="hf-kb-single__category-link">
							<?php echo esc_html( $cat->name ); ?>
						</a>
					<?php endforeach; ?>
				</span>
			<?php endif; ?>
		</div>
	</header>

	<!-- Article Content -->
	<div class="hf-kb-single__content">
		<?php the_content(); ?>
	</div>

	<!-- Was this helpful? -->
	<div class="hf-kb-voting" id="hf-kb-voting" data-article-id="<?php echo esc_attr( $article_id ); ?>" data-nonce="<?php echo esc_attr( $vote_nonce ); ?>">
		<h3 class="hf-kb-voting__title"><?php esc_html_e( 'Was this article helpful?', 'hostforge' ); ?></h3>
		<div class="hf-kb-voting__buttons">
			<button type="button" class="hf-btn hf-btn--vote hf-kb-vote-btn" data-vote="yes">
				<?php esc_html_e( 'Yes', 'hostforge' ); ?>
			</button>
			<button type="button" class="hf-btn hf-btn--vote hf-kb-vote-btn" data-vote="no">
				<?php esc_html_e( 'No', 'hostforge' ); ?>
			</button>
		</div>
		<div class="hf-kb-voting__counts">
			<span class="hf-kb-voting__count hf-kb-voting__count--yes" id="hf-kb-vote-yes">
				<?php
				printf(
					/* translators: %d: number of positive votes */
					esc_html__( '%d found this helpful', 'hostforge' ),
					esc_html( $yes_count )
				);
				?>
			</span>
			<span class="hf-kb-voting__count hf-kb-voting__count--no" id="hf-kb-vote-no">
				<?php
				printf(
					/* translators: %d: number of negative votes */
					esc_html__( '%d did not', 'hostforge' ),
					esc_html( $no_count )
				);
				?>
			</span>
		</div>
	</div>

	<!-- Related Articles -->
	<?php
	if ( ! empty( $related_ids ) && is_array( $related_ids ) ) :
		$related_articles = get_posts(
			array(
				'post_type'      => 'hf_kb_article',
				'post_status'    => 'publish',
				'post__in'       => array_map( 'absint', $related_ids ),
				'orderby'        => 'post__in',
				'posts_per_page' => 10,
			)
		);

		if ( ! empty( $related_articles ) ) :
	?>
		<div class="hf-kb-related">
			<h3 class="hf-kb-related__title"><?php esc_html_e( 'Related Articles', 'hostforge' ); ?></h3>
			<ul class="hf-kb-article-list">
				<?php foreach ( $related_articles as $related ) : ?>
					<li class="hf-kb-article-list__item">
						<a href="<?php echo esc_url( get_permalink( $related->ID ) ); ?>" class="hf-kb-article-list__link">
							<?php echo esc_html( $related->post_title ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php
		endif;
	endif;
	?>
</div>

<?php
	endwhile;
endif;

get_footer();
