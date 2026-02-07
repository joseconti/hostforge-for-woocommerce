<?php 
/**
 * YWSBS_Gutenberg is an log of all transactions
 *
 * @class   YWSBS_Gutenberg
 * @package YITH\Subscription
 * @since   1.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Gutenberg' ) ) {
	/**
	 * Class YWSBS_Gutenberg
	 */
	class YWSBS_Gutenberg {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Constructor
		 * Initialize class and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		private function __construct() {
			add_action( 'init', array( $this, 'gutenberg_integration' ) );

			global $wp_version;
			if ( version_compare( $wp_version, '5.8', '<' ) ) {
				add_filter( 'block_categories', array( $this, 'block_category' ), 100, 2 );
			} else {
				add_filter( 'block_categories_all', array( $this, 'block_category' ), 100, 2 );
			}

		}

		/**
		 * Gutenberg Integration
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function gutenberg_integration() {

			// Register script.
			YITH_WC_Subscription_Assets::get_instance()->add_admin_script(
				'ywsbs-plans-editor-script',
				YITH_YWSBS_URL . 'dist/blocks/index.js',
				array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
				'__return_false', // Let's gutenberg block enqueue this script.
				true
			);

			YITH_WC_Subscription_Assets::get_instance()->localize_script(
				'ywsbs-plans-editor-script',
				'ywsbs_plans_object',
				array(
					'ywsbs_plans_preview' => YITH_YWSBS_ASSETS_URL . '/images/gutenberg_blocks.png',
				)
			);

			// Register style.
			YITH_WC_Subscription_Assets::get_instance()->add_admin_style(
				'ywsbs-plans-editor-style',
				'ywsbs-plans-editor.css',
				array( 'wp-edit-blocks' ),
				'__return_false', // Let's gutenberg block enqueue this style.
			);

			YITH_WC_Subscription_Assets::get_instance()->add_frontend_style(
				'ywsbs-plans',
				'ywsbs-plans.css',
				array(),
				'__return_false', // Let's gutenberg block enqueue this style.
			);

			register_block_type(
				'yith/ywsbs-plans',
				array(
					'editor_script' => 'ywsbs-plans-editor-script',
					'editor_style'  => 'ywsbs-plans-editor-style',
					'style'         => 'ywsbs-plans',
				)
			);

			register_block_type(
				'yith/ywsbs-plan',
				array(
					'editor_script' => 'ywsbs-plans-editor-script',
				)
			);

			register_block_type(
				'yith/ywsbs-price',
				array(
					'editor_script' => 'ywsbs-plans-editor-script',
				)
			);

		}

		/**
		 * Add block category
		 *
		 * @param array   $categories Array block categories array.
		 * @param WP_Post $post       Current post.
		 * @return array block categories
		 */
		public function block_category( $categories, $post ) {

			$found_key = array_search( 'yith-blocks', array_column( $categories, 'slug' ), true );

			if ( ! $found_key ) {
				$categories[] = array(
					'slug'  => 'yith-blocks',
					'title' => _x( 'YITH', '[gutenberg]: Category Name', 'yith-plugin-fw' ),
				);
			}

			return $categories;
		}
	}
}

/**
 * Unique access to instance of YWSBS_Gutenberg class
 *
 * @return YWSBS_Gutenberg
 */
function YWSBS_Gutenberg() { //phpcs:ignore
	return YWSBS_Gutenberg::get_instance();
}

YWSBS_Gutenberg();
