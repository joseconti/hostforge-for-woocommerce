<?php
/**
 * Knowledge Base — Category page.
 *
 * Override: copy this file to theme/hostforge/frontend/kb-category.php
 *
 * @package HostForge\Templates\Frontend
 */

defined( 'ABSPATH' ) || exit;

get_header();

$queried_object = get_queried_object();
$category_name  = '';
$category_desc  = '';

if ( $queried_object instanceof WP_Term ) {
	$category_name = $queried_object->name;
	$category_desc = $queried_object->description;
}
?>

<div class="hf-kb-category">
	<!-- Breadcrumbs -->
	<nav class="hf-kb-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumbs', 'hostforge' ); ?>">
		<a href="<?php echo esc_url( get_post_type_archive_link( 'hf_kb_article' ) ); ?>">
			<?php esc_html_e( 'Knowledge Base', 'hostforge' ); ?>
		</a>
		<span class="hf-kb-breadcrumbs__separator">/</span>
		<span class="hf-kb-breadcrumbs__current"><?php echo esc_html( $category_name ); ?></span>
	</nav>

	<!-- Category Header -->
	<header class="hf-kb-category__header">
		<h1 class="hf-kb-category__title"><?php echo esc_html( $category_name ); ?></h1>
		<?php if ( ! empty( $category_desc ) ) : ?>
			<p class="hf-kb-category__description"><?php echo esc_html( $category_desc ); ?></p>
		<?php endif; ?>
	</header>

	<!-- Articles List -->
	<?php if ( have_posts() ) : ?>
		<div class="hf-kb-category__articles">
			<?php while ( have_posts() ) : the_post(); ?>
				<article class="hf-kb-article-card">
					<h2 class="hf-kb-article-card__title">
						<a href="<?php echo esc_url( get_permalink() ); ?>">
							<?php the_title(); ?>
						</a>
					</h2>
					<?php if ( has_excerpt() || get_the_content() ) : ?>
						<p class="hf-kb-article-card__excerpt">
							<?php echo esc_html( wp_trim_words( get_the_excerpt() ?: get_the_content(), 30, '...' ) ); ?>
						</p>
					<?php endif; ?>
					<span class="hf-kb-article-card__date">
						<?php echo esc_html( get_the_date() ); ?>
					</span>
				</article>
			<?php endwhile; ?>
		</div>

		<?php
		// Pagination.
		$pagination = get_the_posts_pagination(
			array(
				'prev_text' => esc_html__( 'Previous', 'hostforge' ),
				'next_text' => esc_html__( 'Next', 'hostforge' ),
			)
		);

		if ( ! empty( $pagination ) ) :
		?>
			<div class="hf-kb-category__pagination">
				<?php echo wp_kses_post( $pagination ); ?>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<p class="hf-empty"><?php esc_html_e( 'No articles found in this category.', 'hostforge' ); ?></p>
	<?php endif; ?>
</div>

<?php
get_footer();
