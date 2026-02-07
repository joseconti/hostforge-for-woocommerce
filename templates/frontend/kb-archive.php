<?php
/**
 * Knowledge Base — Archive (main page).
 *
 * Override: copy this file to theme/hostforge/frontend/kb-archive.php
 *
 * @package HostForge\Templates\Frontend
 */

defined( 'ABSPATH' ) || exit;

get_header();

$categories = get_terms(
	array(
		'taxonomy'   => 'hf_kb_category',
		'hide_empty' => true,
	)
);

if ( is_wp_error( $categories ) ) {
	$categories = array();
}

// Recent articles.
$recent_articles = get_posts(
	array(
		'post_type'      => 'hf_kb_article',
		'post_status'    => 'publish',
		'posts_per_page' => 10,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation' => 'OR',
			array(
				'key'   => '_hf_visibility',
				'value' => 'public',
			),
			array(
				'key'     => '_hf_visibility',
				'compare' => 'NOT EXISTS',
			),
		),
	)
);

// Popular articles (sorted by helpful votes).
$popular_articles = get_posts(
	array(
		'post_type'      => 'hf_kb_article',
		'post_status'    => 'publish',
		'posts_per_page' => 5,
		'meta_key'       => '_hf_helpful_yes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation' => 'OR',
			array(
				'key'   => '_hf_visibility',
				'value' => 'public',
			),
			array(
				'key'     => '_hf_visibility',
				'compare' => 'NOT EXISTS',
			),
		),
	)
);
?>

<div class="hf-kb-archive">
	<div class="hf-kb-archive__header">
		<h1 class="hf-kb-archive__title"><?php esc_html_e( 'Knowledge Base', 'hostforge' ); ?></h1>

		<form class="hf-kb-search-form" action="<?php echo esc_url( get_post_type_archive_link( 'hf_kb_article' ) ); ?>" method="get">
			<div class="hf-kb-search-form__inner">
				<input
					type="search"
					name="s"
					class="hf-kb-search-form__input"
					placeholder="<?php esc_attr_e( 'Search the knowledge base...', 'hostforge' ); ?>"
					value="<?php echo esc_attr( get_search_query() ); ?>"
				/>
				<input type="hidden" name="post_type" value="hf_kb_article" />
				<button type="submit" class="hf-kb-search-form__button hf-btn hf-btn--primary">
					<?php esc_html_e( 'Search', 'hostforge' ); ?>
				</button>
			</div>
		</form>
	</div>

	<!-- Categories Grid -->
	<?php if ( ! empty( $categories ) ) : ?>
		<div class="hf-kb-categories">
			<h2 class="hf-kb-section-title"><?php esc_html_e( 'Categories', 'hostforge' ); ?></h2>
			<div class="hf-kb-categories__grid">
				<?php foreach ( $categories as $category ) :
					$cat_link  = get_term_link( $category );
					$cat_count = $category->count;
				?>
					<a href="<?php echo esc_url( $cat_link ); ?>" class="hf-kb-category-card">
						<h3 class="hf-kb-category-card__name"><?php echo esc_html( $category->name ); ?></h3>
						<span class="hf-kb-category-card__count">
							<?php
							printf(
								/* translators: %d: number of articles */
								esc_html( _n( '%d article', '%d articles', $cat_count, 'hostforge' ) ),
								esc_html( $cat_count )
							);
							?>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- Popular Articles -->
	<?php if ( ! empty( $popular_articles ) ) : ?>
		<div class="hf-kb-popular">
			<h2 class="hf-kb-section-title"><?php esc_html_e( 'Popular Articles', 'hostforge' ); ?></h2>
			<ul class="hf-kb-article-list">
				<?php foreach ( $popular_articles as $article ) :
					$yes_count = absint( get_post_meta( $article->ID, '_hf_helpful_yes', true ) );
				?>
					<li class="hf-kb-article-list__item">
						<a href="<?php echo esc_url( get_permalink( $article->ID ) ); ?>" class="hf-kb-article-list__link">
							<?php echo esc_html( $article->post_title ); ?>
						</a>
						<?php if ( $yes_count > 0 ) : ?>
							<span class="hf-kb-article-list__votes">
								<?php
								printf(
									/* translators: %d: number of helpful votes */
									esc_html( _n( '%d helpful vote', '%d helpful votes', $yes_count, 'hostforge' ) ),
									esc_html( $yes_count )
								);
								?>
							</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<!-- Recent Articles -->
	<?php if ( ! empty( $recent_articles ) ) : ?>
		<div class="hf-kb-recent">
			<h2 class="hf-kb-section-title"><?php esc_html_e( 'Recent Articles', 'hostforge' ); ?></h2>
			<ul class="hf-kb-article-list">
				<?php foreach ( $recent_articles as $article ) : ?>
					<li class="hf-kb-article-list__item">
						<a href="<?php echo esc_url( get_permalink( $article->ID ) ); ?>" class="hf-kb-article-list__link">
							<?php echo esc_html( $article->post_title ); ?>
						</a>
						<span class="hf-kb-article-list__date">
							<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $article->post_date ) ) ); ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
