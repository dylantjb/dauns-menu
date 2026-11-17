<?php
/**
 * Plugin Name:       Dauns Menu
 * Description:       Shortcodes, styling and behaviour for the Dauns menu page.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Dylan Barker
 * License:           GPL-2.0-or-later
 * Text Domain:       dauns-menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DAUNS_MENU_VERSION', '1.0.0' );
define( 'DAUNS_MENU_PATH', plugin_dir_path( __FILE__ ) );
define( 'DAUNS_MENU_URL', plugin_dir_url( __FILE__ ) );



/**
 * Assets.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'dauns-menu',
		DAUNS_MENU_URL . 'assets/menu.css',
		array(),
		DAUNS_MENU_VERSION
	);

	wp_enqueue_style(
		'fancybox-css',
		'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css'
	);
	wp_enqueue_script(
		'fancybox-js',
		'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js',
		array( 'jquery' ),
		'',
		true
	);

	wp_enqueue_script(
		'dauns-menu',
		DAUNS_MENU_URL . 'assets/menu.js',
		array( 'jquery' ),
		DAUNS_MENU_VERSION,
		true
	);
} );
