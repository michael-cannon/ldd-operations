<?php
/**
 * Plugin Name: Legal Document Deliveries - Operations
 * Plugin URI: https://github.com/michael-cannon/ldd-operations
 * Description: LDD operations system helper
 * Version: 1.0.0
 * Author: Michael Cannon
 * Author URI: http://aihr.us/resume/
 * License: GPLv2 or later
 * Text Domain: ldd-operations
 * Domain Path: /languages
 */


/**
 * Copyright 2014 Michael Cannon (email: mc@aihr.us)
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

if ( ! defined( 'LDD_OPERATIONS_BASE' ) )
	define( 'LDD_OPERATIONS_BASE', plugin_basename( __FILE__ ) );

if ( ! defined( 'LDD_OPERATIONS_DIR' ) )
	define( 'LDD_OPERATIONS_DIR', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'LDD_OPERATIONS_DIR_INC' ) )
	define( 'LDD_OPERATIONS_DIR_INC', LDD_OPERATIONS_DIR . 'includes/' );

if ( ! defined( 'LDD_OPERATIONS_DIR_LIB' ) )
	define( 'LDD_OPERATIONS_DIR_LIB', LDD_OPERATIONS_DIR_INC . 'libraries/' );

if ( ! defined( 'LDD_OPERATIONS_NAME' ) )
	define( 'LDD_OPERATIONS_NAME', 'Legal Document Deliveries - Operations' );

if ( ! defined( 'LDD_OPERATIONS_REQ_BASE' ) )
	define( 'LDD_OPERATIONS_REQ_BASE', 'ldd/ldd.php' );

if ( ! defined( 'LDD_OPERATIONS_REQ_NAME' ) )
	define( 'LDD_OPERATIONS_REQ_NAME', 'Legal Document Deliveries - Core ' );

if ( ! defined( 'LDD_OPERATIONS_REQ_SLUG' ) )
	define( 'LDD_OPERATIONS_REQ_SLUG', 'ldd' );

if ( ! defined( 'LDD_OPERATIONS_REQ_VERSION' ) )
	define( 'LDD_OPERATIONS_REQ_VERSION', '1.0.0' );

if ( ! defined( 'LDD_OPERATIONS_VERSION' ) )
	define( 'LDD_OPERATIONS_VERSION', '1.0.0' );

require_once LDD_OPERATIONS_DIR_INC . 'requirements.php';

global $ldd_operations_activated;

$ldd_operations_activated = true;
if ( ! ldd_operations_requirements_check() ) {
	$ldd_operations_activated = false;

	return false;
}

require_once LDD_OPERATIONS_DIR_INC . 'functions.php';
require_once LDD_OPERATIONS_DIR_INC . 'class-ldd-operations.php';


add_action( 'plugins_loaded', 'ldd_operations_init', 99 );


/**
 *
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 */
if ( ! function_exists( 'ldd_operations_init' ) ) {
	function ldd_operations_init() {
		if ( LDD_Operations::version_check() ) {
			global $LDD_Operations;
			if ( is_null( $LDD_Operations ) )
				$LDD_Operations = new LDD_Operations();
			
			do_action( 'ldd_operations_init' );
		}
	}
}


register_activation_hook( __FILE__, array( 'LDD_Operations', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'LDD_Operations', 'deactivation' ) );
register_uninstall_hook( __FILE__, array( 'LDD_Operations', 'uninstall' ) );


if ( ! function_exists( 'ldd_operations_shortcode' ) ) {
	function ldd_operations_shortcode( $atts ) {
		return LDD_Operations::ldd_operations_shortcode( $atts );
	}
}

?>
