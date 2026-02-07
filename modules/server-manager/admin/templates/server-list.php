<?php
/**
 * Server List Template.
 *
 * @package HostForge\Modules\ServerManager\Admin
 * @var HF_Server_List_Table $list_table
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap hf-wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Servers', 'hostforge' ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-servers&action=add' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New Server', 'hostforge' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php $list_table->views(); ?>

	<form method="get">
		<input type="hidden" name="page" value="hostforge-servers" />
		<?php
		$list_table->search_box( __( 'Search Servers', 'hostforge' ), 'hf-server-search' );
		$list_table->display();
		?>
	</form>
</div>
