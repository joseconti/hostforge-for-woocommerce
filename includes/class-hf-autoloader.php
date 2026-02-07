<?php
/**
 * PSR-4 Autoloader for HostForge.
 *
 * Maps HostForge\ namespace to includes/ directory
 * and module namespaces to modules/{slug}/ directories.
 *
 * @package HostForge
 */

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	/**
	 * Autoload callback.
	 *
	 * @param string $class Fully-qualified class name.
	 */
	function ( string $class ): void {
		// Only handle HostForge namespace.
		$prefix = 'HostForge\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		// Strip prefix.
		$relative = substr( $class, strlen( $prefix ) );

		// Module namespaces: HostForge\Modules\{ModuleName}\Class
		if ( 0 === strpos( $relative, 'Modules\\' ) ) {
			$module_relative = substr( $relative, strlen( 'Modules\\' ) );
			$parts           = explode( '\\', $module_relative );

			if ( count( $parts ) >= 2 ) {
				// Convert ModuleName to module-name (CamelCase to kebab-case).
				$module_name = strtolower( (string) preg_replace( '/([a-z])([A-Z])/', '$1-$2', $parts[0] ) );
				$class_name  = end( $parts );
				$sub_path    = '';

				// Build sub-path for nested namespaces (e.g. Admin, Api, Providers).
				if ( count( $parts ) > 2 ) {
					$sub_parts = array_slice( $parts, 1, -1 );
					$sub_path  = implode(
						'/',
						array_map(
							function ( string $part ): string {
								return strtolower( $part );
							},
							$sub_parts
						)
					) . '/';
				}

				$file = HOSTFORGE_PLUGIN_DIR . 'modules/' . $module_name . '/' . $sub_path . hostforge_class_to_file( $class_name );

				if ( file_exists( $file ) ) {
					require_once $file;
				}
			}
			return;
		}

		// Core namespaces.
		$parts      = explode( '\\', $relative );
		$class_name = end( $parts );
		$sub_path   = '';

		// Map sub-namespaces to directories.
		if ( count( $parts ) > 1 ) {
			$namespace_dirs = array(
				'Abstracts'  => 'abstracts',
				'Interfaces' => 'interfaces',
				'Traits'     => 'traits',
				'Helpers'    => 'helpers',
				'Admin'      => 'admin',
			);

			$sub_parts = array_slice( $parts, 0, -1 );
			$mapped    = array();

			foreach ( $sub_parts as $part ) {
				$mapped[] = $namespace_dirs[ $part ] ?? strtolower( $part );
			}

			$sub_path = implode( '/', $mapped ) . '/';
		}

		$file = HOSTFORGE_PLUGIN_DIR . 'includes/' . $sub_path . hostforge_class_to_file( $class_name );

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

/**
 * Convert a class name to its file name following WordPress naming conventions.
 *
 * @param string $class_name The class name (without namespace).
 * @return string The file name.
 */
function hostforge_class_to_file( string $class_name ): string {
	// Determine file prefix based on naming pattern.
	$prefixes = array(
		'abstract'  => 'abstract-',
		'interface' => 'interface-',
		'trait'     => 'trait-',
	);

	$file_prefix = 'class-';
	$name        = $class_name;

	foreach ( $prefixes as $keyword => $prefix ) {
		$pattern = '/^(HF_)?' . ucfirst( $keyword ) . '_?/';
		if ( preg_match( $pattern, $class_name ) ) {
			$file_prefix = $prefix;
			break;
		}
	}

	// Check for interface/trait naming: e.g. HF_Has_Logs (trait), HF_Panel_Provider (interface).
	if ( 0 === strpos( $class_name, 'HF_Has_' ) ) {
		$file_prefix = 'trait-';
	}

	// Convert CamelCase/PascalCase to kebab-case.
	$name = (string) preg_replace( '/([a-z])([A-Z])/', '$1-$2', $name );
	$name = strtolower( str_replace( '_', '-', $name ) );

	return $file_prefix . $name . '.php';
}
