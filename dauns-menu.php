<?php
/**
 * Plugin Name:       Dauns Menu
 * Description:       Shortcodes, styling and behaviour for the Dauns menu page.
 * Version:           1.0.2
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Dylan Barker
 * License:           GPL-2.0-or-later
 * Text Domain:       dauns-menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DAUNS_MENU_VERSION', '1.0.2' );
define( 'DAUNS_MENU_PATH', plugin_dir_path( __FILE__ ) );
define( 'DAUNS_MENU_URL', plugin_dir_url( __FILE__ ) );



/**
 * Assets — menu page only.
 *
 * Priority 20 so this queues AFTER the theme's stylesheets. A plugin registers
 * at priority 10, which would print our CSS ahead of the theme's and lose the
 * cascade everywhere specificity is equal.
 */
/**
 * Does this request render anything from the menu shortcodes?
 */
function dauns_mn_has_menu_assets() {
	if ( is_page_template( 'template-menu.php' ) ) {
		return true;
	}
	if ( ! is_singular() ) {
		return false;
	}
	$post = get_queried_object();
	if ( ! $post || empty( $post->post_content ) ) {
		return false;
	}
	foreach ( array( 'menu-item', 'menu-section', 'menu-nav', 'menu-note', 'menu-deal', 'menu-sub' ) as $sc ) {
		if ( has_shortcode( $post->post_content, $sc ) ) {
			return true;
		}
	}
	return false;
}

add_action( 'wp_enqueue_scripts', function () {
	if ( ! dauns_mn_has_menu_assets() ) {
		return;
	}

	wp_enqueue_style(
		'dauns-menu',
		DAUNS_MENU_URL . 'assets/menu.css',
		array(),
		filemtime( DAUNS_MENU_PATH . 'assets/menu.css' )
	);

	/* Fancybox first, so it is on the page before menu.js runs. */
	wp_enqueue_style(
		'fancybox',
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
}, 20 );

/* =========================================================================
   HELPERS
   ========================================================================= */

function dauns_mn_tags( $raw ) {
	if ( ! $raw ) { return ''; }
	$map = array(
		'gf'    => 'Gluten free',
		'gfo'   => 'Gluten-free option',
		'nfo'   => 'Nut-free option',
		'nuts'  => 'Contains nuts',
		'alc'   => 'Contains alcohol',
		'spicy' => 'Spicy',
	);
	$out = '';
	foreach ( explode( ',', $raw ) as $t ) {
		$t = strtolower( trim( $t ) );
		if ( isset( $map[ $t ] ) ) {
			$out .= '<span class="mn-tag mn-tag-' . esc_attr( $t ) . '">' . esc_html( $map[ $t ] ) . '</span>';
		}
	}
	return $out ? '<p class="mn-tags">' . $out . '</p>' : '';
}

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
   EDITOR GUARD — catches the failure mode the shortcodes can't report
   themselves: an unbalanced tag silently swallows the content that follows.
   ========================================================================= */

function dauns_menu_check( $content ) {
	$problems = array();

	preg_match_all( '/\[\/?menu-(?:section|item)\b[^\]]*\]/i', $content, $m );

	$stack = array();

	foreach ( $m[0] as $tag ) {
		$closing = ( 0 === strpos( $tag, '[/' ) );
		$type    = ( false !== stripos( $tag, 'menu-section' ) ) ? 'section' : 'item';

		$label = '';
		foreach ( array( 'name', 'title' ) as $attr ) {
			if ( preg_match( '/\b' . $attr . '\s*=\s*"((?:\\\\.|[^"\\\\])*)"/i', $tag, $a ) ) {
				$label = str_replace( array( '\\"', "\\'" ), array( '"', "'" ), $a[1] );
				break;
			}
		}

		if ( ! $closing ) {
			if ( 'item' === $type ) {
				for ( $i = count( $stack ) - 1; $i >= 0; $i-- ) {
					if ( 'section' === $stack[ $i ]['type'] ) {
						$stack[ $i ]['items']++;
						break;
					}
				}
			}
			$stack[] = array( 'type' => $type, 'label' => $label, 'items' => 0 );
			continue;
		}

		$matched = false;
		while ( $stack ) {
			$open = array_pop( $stack );

			if ( $open['type'] === $type ) {
				$matched = true;
				if ( 'section' === $type && 0 === $open['items'] ) {
					$problems[] = sprintf(
						'The section%s has no dishes in it.',
						$open['label'] ? ' &ldquo;' . esc_html( $open['label'] ) . '&rdquo;' : ''
					);
				}
				break;
			}

			$problems[] = sprintf(
				'The %s%s is never closed — add %s.',
				$open['type'],
				$open['label'] ? ' &ldquo;' . esc_html( $open['label'] ) . '&rdquo;' : '',
				'<code>[/menu-' . $open['type'] . ']</code>'
			);
		}

		if ( ! $matched ) {
			$problems[] = sprintf( 'A stray %s has nothing to close.', '<code>' . esc_html( $tag ) . '</code>' );
		}
	}

	foreach ( $stack as $open ) {
		$problems[] = sprintf(
			'The %s%s is never closed — add %s.',
			$open['type'],
			$open['label'] ? ' &ldquo;' . esc_html( $open['label'] ) . '&rdquo;' : '',
			'<code>[/menu-' . $open['type'] . ']</code>'
		);
	}

	return $problems;
}

/* =========================================================================
   SHORTCODES
   ========================================================================= */

add_shortcode( 'menu-nav', function () {
	return '<nav class="mn-jump" id="mn-jump" aria-label="Menu sections"></nav>';
} );

add_shortcode( 'menu-sub', function ( $atts ) {
	$a  = shortcode_atts( array( 'title' => '', 'note' => '' ), $atts );
	$id = sanitize_title( $a['title'] );
	$n  = $a['note'] !== '' ? '<span class="mn-sub-note">' . esc_html( $a['note'] ) . '</span>' : '';
	return '<h3 class="mn-sub" id="' . esc_attr( $id ) . '" data-title="' . esc_attr( $a['title'] ) . '">'
		. esc_html( $a['title'] ) . $n . '</h3>';
} );

add_shortcode( 'menu-note', function ( $atts, $content = '' ) {
	return '<div class="mn-note">' . wp_kses_post( $content ) . '</div>';
} );

add_shortcode( 'menu-deal', function ( $atts, $content = '' ) {
	return '<p class="mn-deal">' . wp_kses_post( $content ) . '</p>';
} );

add_shortcode( 'menu-sub', function ( $atts ) {
	$a = shortcode_atts( array( 'title' => '', 'note' => '' ), $atts );
	$n = $a['note'] !== '' ? '<span class="mn-sub-note">' . esc_html( $a['note'] ) . '</span>' : '';
	return '<h3 class="mn-sub">' . esc_html( $a['title'] ) . $n . '</h3>';
} );

add_shortcode( 'menu-section', function ( $atts, $content = '' ) {
	$a  = shortcode_atts( array( 'title' => '', 'badge' => '', 'open' => '' ), $atts );
	$id = sanitize_title( $a['title'] );
	$GLOBALS['dauns_mn_cur'] = $id;
	$GLOBALS['dauns_mn_schema'][ $id ] = array( 'name' => $a['title'], 'items' => array() );
	$b = $a['badge'] ? '<span class="mn-badge">' . esc_html( $a['badge'] ) . '</span>' : '';
	return '<section class="mn-sec" id="' . esc_attr( $id ) . '" data-title="' . esc_attr( $a['title'] ) . '"'
		. ( $a['open'] ? ' data-open="1"' : '' ) . '>'
		. '<h2 class="mn-h"><button type="button" class="mn-h-btn" aria-expanded="true">'
		. '<span class="mn-h-t">' . esc_html( $a['title'] ) . '</span>' . $b
		. '<span class="mn-chev" aria-hidden="true"></span></button></h2>'
		. '<div class="mn-sec-body">' . do_shortcode( $content ) . '</div></section>';
} );

add_shortcode( 'menu-item', function ( $atts, $content = '' ) {
	$a = shortcode_atts( array(
		'name' => '', 'tags' => '', 'img' => '', 'alt' => '', 'soldout' => '',
		'featured' => '', 'opts' => '', 'price' => '', 'eatin' => '', 'takeaway' => '',
	), $atts );

	$out = ! empty( $a['soldout'] );

	$cur = isset( $GLOBALS['dauns_mn_cur'] ) ? $GLOBALS['dauns_mn_cur'] : '';
	if ( $a['name'] !== '' && $cur !== '' ) {
		$GLOBALS['dauns_mn_schema'][ $cur ]['items'][] = $a['name'];
	}

	// Lift [menu-option] lines out of the description before formatting it.
	$option_rows = '';
	if ( preg_match_all( '/\[menu-option\s+([^\]]*)\]/i', $content, $mm ) ) {
		foreach ( $mm[1] as $attr_string ) {
			/* wptexturize() runs before do_shortcode() and rewrites the
			   straight quote delimiters as HTML entities. Neither form is
			   a delimiter to shortcode_parse_atts(), so normalise both. */
			$attr_string = dauns_mn_fix_quotes( $attr_string );
			$o = shortcode_parse_atts( $attr_string );
			if ( ! is_array( $o ) || empty( $o['label'] ) ) { continue; }
			$note = isset( $o['note'] ) ? ' <em class="mn-opt-n">' . esc_html( $o['note'] ) . '</em>' : '';
			$option_rows .= '<li>' . esc_html( $o['label'] ) . $note . '</li>';
		}
		$content = preg_replace( '/\[menu-option\s+[^\]]*\]/i', '', $content );
	}
	$options = $option_rows
		? ( $a['opts'] !== '' ? '<p class="mn-opts-h">' . esc_html( $a['opts'] ) . '</p>' : '' )
			. '<ul class="mn-opts">' . $option_rows . '</ul>'
		: '';

	$desc = '';
	if ( $content !== '' ) {
		$t = preg_replace( array( '/<\/?p[^>]*>/i', '/<br\s*\/?>/i' ), ' ', $content );
		$t = trim( preg_replace( '/\s+/', ' ', $t ) );
		if ( $t !== '' ) { $desc = '<p class="mn-desc">' . wp_kses_post( $t ) . '</p>'; }
	}

	$alt   = $a['alt'] !== '' ? $a['alt'] : $a['name'];
	$media = dauns_mn_media( $a['img'], $alt, $a['name'], ! empty( $a['featured'] ) );

	$price = '';
	$in    = $a['eatin'] !== '' ? $a['eatin'] : $a['price'];
	if ( $in !== '' || $a['takeaway'] !== '' ) {
		$price = '<span class="mn-price">'
			. ( $in !== '' ? '<span class="mn-p-in">&pound;' . esc_html( $in ) . '</span>' : '' )
			. ( $a['takeaway'] !== '' ? '<span class="mn-p-ta">&pound;' . esc_html( $a['takeaway'] ) . '</span>' : '' )
			. '</span>';
	}

	$flag = $out ? '<span class="mn-out">Sold out</span>' : '';

	if ( $a['featured'] && $media ) {
		return '<div class="mn-card' . ( $out ? ' is-out' : '' ) . '">'
			. '<div class="mn-card-head"><span class="mn-name mn-name-lg">' . esc_html( $a['name'] ) . '</span>'
			. $flag . $price . '</div>'
			. '<div class="mn-card-media">' . $media . '</div>'
			. '<div class="mn-card-body">' . $desc . $options . dauns_mn_tags( $a['tags'] ) . '</div>'
			. '</div>';
	}

	return '<div class="mn-item' . ( $out ? ' is-out' : '' ) . '">'
		. '<div class="mn-item-b"><span class="mn-name">' . esc_html( $a['name'] ) . '</span>'
		. $flag . $price . $desc . $options . dauns_mn_tags( $a['tags'] ) . '</div>'
		. ( $media ? '<span class="mn-thumb">' . $media . '</span>' : '' )
		. '</div>';

} );


/* =========================================================================
   MENU SCHEMA
   ========================================================================= */

add_action( 'wp_footer', function () {
	if ( ! is_page_template( 'template-menu.php' ) ) { return; }

	$schema   = isset( $GLOBALS['dauns_mn_schema'] ) ? (array) $GLOBALS['dauns_mn_schema'] : array();
	$sections = array();

	foreach ( $schema as $sec ) {
		if ( empty( $sec['items'] ) ) { continue; }
		$items = array();
		foreach ( $sec['items'] as $n ) {
			$items[] = array(
				'@type' => 'MenuItem',
				'name'  => html_entity_decode( $n, ENT_QUOTES, 'UTF-8' ),
			);
		}
		$sections[] = array(
			'@type'       => 'MenuSection',
			'name'        => html_entity_decode( $sec['name'], ENT_QUOTES, 'UTF-8' ),
			'hasMenuItem' => $items,
		);
	}
	if ( ! $sections ) { return; }

	echo '<script type="application/ld+json">' . wp_json_encode( array(
		'@context'       => 'https://schema.org',
		'@type'          => 'Menu',
		'@id'            => home_url( '/menu/#menu' ),
		'name'           => 'Dauns Menu',
		'provider'       => array( '@id' => home_url( '/#organization' ) ),
		'hasMenuSection' => $sections,
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}, 30 );

add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-page' !== $screen->id ) {
		return;
	}

	$found = get_pages( array(
		'meta_key'   => '_wp_page_template',
		'meta_value' => 'template-menu.php',
		'number'     => 1,
	) );
	if ( ! $found ) {
		return;
	}

	$page = $found[0];
	if ( ! current_user_can( 'edit_post', $page->ID ) ) {
		return;
	}
	if ( ! dauns_menu_check( $page->post_content ) ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p><strong>Menu check —</strong> the <a href="%s">Menu page</a> has a formatting problem and will look wrong on the website.</p></div>',
		esc_url( get_edit_post_link( $page->ID ) )
	);
} );
/* wptexturize() runs at the_content priority 10 and rewrites straight
   quotes as HTML entities (&#8220; and &#8221;), which shortcode_parse_atts()
   does not recognise as delimiters. Convert both the entity and the raw
   character form back to a plain quote inside shortcode brackets only. */
function dauns_mn_fix_quotes( $text ) {
	return str_replace(
		array( '&#8220;', '&#8221;', '&ldquo;', '&rdquo;', "\xE2\x80\x9C", "\xE2\x80\x9D" ),
		'"',
		$text
	);
}

add_filter( 'the_content', function ( $content ) {
	if ( false === strpos( $content, '[menu-' ) ) {
		return $content;
	}
	return preg_replace_callback( '/\[[^\]]*\]/', function ( $tag ) {
		return dauns_mn_fix_quotes( $tag[0] );
	}, $content );
}, 10 );

/**
 * Version the menu assets by file modification time, so editing a file is
 * all it takes to bust the browser cache — no version bumping.
 */
add_filter( 'style_loader_src', function ( $src, $handle ) {
	if ( 'dauns-menu' === $handle ) {
		$src = add_query_arg( 'ver', filemtime( DAUNS_MENU_PATH . 'assets/menu.css' ), $src );
	}
	return $src;
}, 99999, 2 );

add_filter( 'script_loader_src', function ( $src, $handle ) {
	if ( 'dauns-menu' === $handle ) {
		$src = add_query_arg( 'ver', filemtime( DAUNS_MENU_PATH . 'assets/menu.js' ), $src );
	}
	return $src;
}, 10, 2 );
