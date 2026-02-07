<?php
/**
 * Ticket list admin template.
 *
 * @package HostForge\Modules\SupportDesk\Admin
 * @var \HostForge\Modules\SupportDesk\Admin\HF_Ticket_List_Table $list_table
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Tickets', 'hostforge' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-tickets&action=new' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New', 'hostforge' ); ?>
	</a>
	<hr class="wp-header-end">

	<div class="hf-ticket-nav">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-tickets&action=departments' ) ); ?>" class="button">
			<?php esc_html_e( 'Departments', 'hostforge' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-tickets&action=canned' ) ); ?>" class="button">
			<?php esc_html_e( 'Canned Responses', 'hostforge' ); ?>
		</a>
	</div>

	<?php $list_table->views(); ?>

	<form method="get">
		<input type="hidden" name="page" value="hostforge-tickets" />
		<?php
		$list_table->search_box( __( 'Search Tickets', 'hostforge' ), 'hf-ticket-search' );
		$list_table->display();
		?>
	</form>
</div>
