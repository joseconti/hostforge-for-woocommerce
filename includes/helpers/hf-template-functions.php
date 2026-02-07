<?php
/**
 * Template helper functions.
 *
 * @package HostForge\Helpers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load a HostForge template.
 *
 * Looks for overrides in theme/hostforge/ before falling back to plugin templates.
 *
 * @param string $template_name Template name (relative to templates/).
 * @param array  $args          Variables to make available in the template.
 * @return void
 */
function hf_get_template( string $template_name, array $args = array() ): void {
	// Allow themes to override.
	$theme_path = get_stylesheet_directory() . '/hostforge/' . $template_name;

	if ( file_exists( $theme_path ) ) {
		$template = $theme_path;
	} else {
		$template = HOSTFORGE_PLUGIN_DIR . 'templates/' . $template_name;
	}

	/**
	 * Filter the template path before including.
	 *
	 * @param string $template      Full path to template.
	 * @param string $template_name Template name.
	 * @param array  $args          Template arguments.
	 */
	$template = apply_filters( 'hostforge_template_path', $template, $template_name, $args );

	if ( ! file_exists( $template ) ) {
		return;
	}

	// Extract variables for the template.
	if ( ! empty( $args ) ) {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $args, EXTR_SKIP );
	}

	include $template;
}

/**
 * Get a HostForge template as a string.
 *
 * @param string $template_name Template name (relative to templates/).
 * @param array  $args          Variables to make available in the template.
 * @return string Rendered template HTML.
 */
function hf_get_template_html( string $template_name, array $args = array() ): string {
	ob_start();
	hf_get_template( $template_name, $args );
	return ob_get_clean();
}

/**
 * Locate a HostForge template.
 *
 * Checks theme overrides first, then falls back to plugin templates directory.
 *
 * @param string $template_name Template name (relative to templates/).
 * @return string|false Full path to template or false if not found.
 */
function hf_locate_template( string $template_name ): string|false {
	// Check theme override.
	$theme_path = get_stylesheet_directory() . '/hostforge/' . $template_name;

	if ( file_exists( $theme_path ) ) {
		return $theme_path;
	}

	// Plugin default.
	$plugin_path = HOSTFORGE_PLUGIN_DIR . 'templates/' . $template_name;

	if ( file_exists( $plugin_path ) ) {
		return $plugin_path;
	}

	return false;
}
