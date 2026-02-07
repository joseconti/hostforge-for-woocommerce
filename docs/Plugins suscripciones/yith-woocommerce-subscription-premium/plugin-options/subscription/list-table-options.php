<?php
/**
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package YITH\Subscription
 * @since   2.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

return array(
	'subscription-list-table' => array(
		'list-table' => array(
			'type'      => 'post_type',
			'post_type' => YITH_YWSBS_POST_TYPE,
			'wp-list-style' => 'classic',
			'wp-list-auto-h-scroll' => true,
		),
	),
);
