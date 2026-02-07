<?php
/**
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @since   3.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

// Exit if accessed directly.
defined( 'YITH_YWSBS_VERSION' ) || exit;

return array(
	'modules' => array(
		'modules' => array(
			'type'           => 'custom_tab',
			'action'         => 'yith_ywsbs_modules_tab',
			'show_container' => true,
		),
	),
);
