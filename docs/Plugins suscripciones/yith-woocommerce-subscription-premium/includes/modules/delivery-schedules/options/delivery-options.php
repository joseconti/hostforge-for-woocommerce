<?php
/**
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @since   2.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

return array(
	'delivery' => array(
		'delivery-options' => array(
			'type'       => 'multi_tab',
			'nav-layout' => 'horizontal',
			'sub-tabs'   => array(
				'delivery-list-table' => array(
					'title'       => __( 'All delivery schedules', 'yith-woocommerce-subscription' ),
					'description' => '<a class="button-primary ywsbs-export-button" href="' . add_query_arg( array( 'action' => 'ywsbs_export_shipping_list' ), admin_url( 'admin.php' ) ) . '"><i class="ywsbs-icon-save_alt"></i>' . esc_html__( 'Download shipping list', 'yith-woocommerce-subscription' ) . '</a>',
				),
				'delivery-settings'   => array(
					'title' => __( 'Delivery schedules settings', 'yith-woocommerce-subscription' ),
				),
			),
		),
	),
);
