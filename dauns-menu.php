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

/* =========================================================================
   HELPERS
   ========================================================================= */

function dauns_mn_img_id( $ref ) {
	$ref = trim( $ref );
	if ( $ref === '' ) { return 0; }
	if ( ctype_digit( $ref ) ) { return (int) $ref; }
	if ( preg_match( '#^(https?://|/)#', $ref ) ) { return 0; }

	static $cache = array();
	if ( isset( $cache[ $ref ] ) ) { return $cache[ $ref ]; }

	global $wpdb;
	$cache[ $ref ] = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta}
		 WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s
		 ORDER BY post_id DESC LIMIT 1",
		'%' . $wpdb->esc_like( $ref ) . '%'
	) );
	return $cache[ $ref ];
}

function dauns_mn_media( $img, $alt, $name, $big = false ) {
	if ( $img === '' ) { return ''; }

	$size = $big ? 'large' : 'gallery-thumb';
	$id   = dauns_mn_img_id( $img );
	if ( $id ) {
		$thumb = wp_get_attachment_image( $id, $size, false, array( 'class' => 'mn-img', 'alt' => $alt, 'loading' => 'lazy' ) );
		$full  = wp_get_attachment_image_url( $id, $big ? 'full' : 'large' );
	} elseif ( preg_match( '#^(https?://|/)#', $img ) ) {
		$thumb = '<img class="mn-img" loading="lazy" alt="' . esc_attr( $alt ) . '" src="' . esc_url( $img ) . '">';
		$full  = $img;
	} else {
		return '';
	}

	if ( ! $thumb || ! $full ) { return ''; }
	return '<a class="mn-zoom" href="' . esc_url( $full ) . '" data-caption="' . esc_attr( $name ) . '">' . $thumb . '</a>';
}

/* =========================================================================
   SHORTCODES
   ========================================================================= */

add_shortcode( 'menu-sub', function ( $atts ) {
	$a = shortcode_atts( array( 'title' => '', 'note' => '' ), $atts );
	$n = $a['note'] !== '' ? '<span class="mn-sub-note">' . esc_html( $a['note'] ) . '</span>' : '';
	return '<h3 class="mn-sub">' . esc_html( $a['title'] ) . $n . '</h3>';
} );

add_shortcode( 'menu-section', function ( $atts, $content = '' ) {
	$a  = shortcode_atts( array( 'title' => '', 'badge' => '' ), $atts );
	$id = sanitize_title( $a['title'] );
	$b = $a['badge'] ? '<span class="mn-badge">' . esc_html( $a['badge'] ) . '</span>' : '';
	return '<section class="mn-sec" id="' . esc_attr( $id ) . '" data-title="' . esc_attr( $a['title'] ) . '">'
		. '<h2 class="mn-h"><button type="button" class="mn-h-btn">'
		. '<span class="mn-h-t">' . esc_html( $a['title'] ) . '</span>' . $b . '</button></h2>'
		. '<div class="mn-sec-body">' . do_shortcode( $content ) . '</div></section>';
} );

add_shortcode( 'menu-item', function ( $atts, $content = '' ) {
	$a = shortcode_atts( array(
		'name' => '', 'img' => '', 'alt' => '', 'featured' => '', 'price' => '',
	), $atts );

	$desc = '';
	if ( $content !== '' ) {
		$t = preg_replace( array( '/<\/?p[^>]*>/i', '/<br\s*\/?>/i' ), ' ', $content );
		$t = trim( preg_replace( '/\s+/', ' ', $t ) );
		if ( $t !== '' ) { $desc = '<p class="mn-desc">' . wp_kses_post( $t ) . '</p>'; }
	}

	$alt   = $a['alt'] !== '' ? $a['alt'] : $a['name'];
	$media = dauns_mn_media( $a['img'], $alt, $a['name'], ! empty( $a['featured'] ) );

	$price = $a['price'] !== '' ? '<span class="mn-price">&pound;' . esc_html( $a['price'] ) . '</span>' : '';

	if ( $a['featured'] && $media ) {
		return '<div class="mn-card">'
			. '<div class="mn-card-head"><span class="mn-name mn-name-lg">' . esc_html( $a['name'] ) . '</span>'
			. $price . '</div>'
			. '<div class="mn-card-media">' . $media . '</div>'
			. '<div class="mn-card-body">' . $desc . '</div>'
			. '</div>';
	}

	return '<div class="mn-item">'
		. '<div class="mn-item-b"><span class="mn-name">' . esc_html( $a['name'] ) . '</span>'
		. $price . $desc . '</div>'
		. ( $media ? '<span class="mn-thumb">' . $media . '</span>' : '' )
		. '</div>';

} );
