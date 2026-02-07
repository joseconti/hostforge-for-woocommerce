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
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

return array(
	'subscription' => array(
		'subscription-options' => array(
			'type'       => 'multi_tab',
			'nav-layout' => 'horizontal',
			'sub-tabs'   => array(
				'subscription-list-table' => array(
					'title' => esc_html__( 'All subscriptions', 'yith-woocommerce-subscription' ),
				),
				'subscription-activities' => array(
					'title' => esc_html_x( 'Subscription activities', 'Admin recap panel with all subscriptions', 'yith-woocommerce-subscription' ),
				),
			),
		),
	),
);
