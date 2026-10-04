<?php
/**
 * Site institutionnel de l'Université d'Ebolowa.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

define( 'SUEB_VERSION', '0.1.0' );
define( 'SUEB_DIR', get_template_directory() );
define( 'SUEB_URI', get_template_directory_uri() );

require SUEB_DIR . '/inc/config.php';
require SUEB_DIR . '/inc/outils.php';
require SUEB_DIR . '/inc/types.php';
require SUEB_DIR . '/inc/champs.php';
require SUEB_DIR . '/inc/personnaliser.php';
require SUEB_DIR . '/inc/accueil.php';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'post-formats', array( 'video', 'audio' ) );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_image_size( 'sueb-portrait', 600, 750, true );
	add_image_size( 'sueb-carte', 960, 640, true );
	register_nav_menus( array(
		'principal' => 'Menu principal',
		'pied'      => 'Pied de page',
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	$v = SUEB_VERSION;
	wp_enqueue_style( 'sueb-polices', SUEB_URI . '/assets/css/polices.css', array(), $v );
	wp_enqueue_style( 'sueb-site', SUEB_URI . '/assets/css/site.css', array( 'sueb-polices' ), $v );
	wp_enqueue_script( 'sueb-site', SUEB_URI . '/assets/js/site.js', array(), $v, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

/* Les polices sont chargées en priorité : pas de saut du titre au chargement. */
add_action( 'wp_head', function () {
	echo "<script>document.documentElement.classList.add('js')</script>\n";
	foreach ( array( 'source-serif-4-normal-latin', 'source-sans-3-normal-latin' ) as $f ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( SUEB_URI . '/assets/fonts/' . $f . '.woff2' ) );
	}
}, 1 );
