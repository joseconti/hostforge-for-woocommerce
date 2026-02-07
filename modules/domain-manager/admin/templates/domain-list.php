<?php
/**
 * Admin template: Domain List.
 *
 * @package HostForge\Modules\DomainManager\Admin
 * @var HF_Domain_List_Table $list_table
 */

defined( 'ABSPATH' ) || exit;

$tabs = array(
	'domains'     => __( 'Domains', 'hostforge' ),
	'tld-pricing' => __( 'TLD Pricing', 'hostforge' ),
	'registrar'   => __( 'Registrar Settings', 'hostforge' ),
);

$current_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ?? 'domains' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Domain Manager', 'hostforge' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $tabs as $slug => $label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-domains&tab=' . $slug ) ); ?>"
				class="nav-tab <?php echo $current_tab === $slug ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<form method="get" id="hf-domain-list-form">
		<input type="hidden" name="page" value="hostforge-domains" />
		<?php
		$list_table->search_box( __( 'Search Domains', 'hostforge' ), 'hf-domain-search' );
		$list_table->views();
		$list_table->display();
		?>
	</form>
</div>
