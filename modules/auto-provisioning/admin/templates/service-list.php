<?php
/**
 * Service list admin template.
 *
 * @package HostForge\Modules\AutoProvisioning\Admin
 * @var HF_Service_List_Table $list_table
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Hosting Services', 'hostforge' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-services&action=settings' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Automation Settings', 'hostforge' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php $list_table->views(); ?>

	<form method="get">
		<input type="hidden" name="page" value="hostforge-services" />
		<?php
		$list_table->search_box( __( 'Search Services', 'hostforge' ), 'hf-service-search' );
		$list_table->display();
		?>
	</form>
</div>
