<?php
/**
 * Page « Toutes les actualités » (/actualites/) : la liste paginée de tous
 * les articles publiés. L'adresse ne dépend d'aucun réglage de WordPress
 * (pas besoin de page « Articles » dans Réglages › Lecture).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/** Adresse de la liste de toutes les actualités. */
function sueb_url_actualites() {
	return home_url( '/actualites/' );
}

add_action( 'init', function () {
	add_rewrite_rule( '^actualites/?$', 'index.php?sueb_actus=1', 'top' );
	add_rewrite_rule( '^actualites/page/([0-9]+)/?$', 'index.php?sueb_actus=1&paged=$matches[1]', 'top' );
} );

/* Les règles d'adresses s'enregistrent une seule fois (inutile d'aller dans Réglages › Permaliens). */
add_action( 'init', function () {
	if ( '1' !== get_option( 'sueb_regles_actus' ) ) {
		flush_rewrite_rules( false );
		update_option( 'sueb_regles_actus', '1' );
	}
}, 99 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'sueb_actus';
	return $vars;
} );

/* La requête : tous les articles, les plus récents d'abord, douze par page. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() || ! $q->get( 'sueb_actus' ) ) {
		return;
	}
	$q->set( 'post_type', 'post' );
	$q->set( 'post_status', 'publish' );
	$q->set( 'posts_per_page', 12 );
	$q->set( 'ignore_sticky_posts', true );
	/* Ce n'est ni l'accueil ni la page des articles de WordPress. */
	$q->is_home    = false;
	$q->is_archive = true;
} );

add_filter( 'template_include', function ( $modele ) {
	if ( get_query_var( 'sueb_actus' ) ) {
		$fichier = SUEB_DIR . '/templates/liste-actualites.php';
		if ( file_exists( $fichier ) ) {
			status_header( 200 );
			return $fichier;
		}
	}
	return $modele;
} );

add_filter( 'document_title_parts', function ( $parts ) {
	if ( get_query_var( 'sueb_actus' ) ) {
		$parts['title'] = 'Toutes les actualités';
	}
	return $parts;
} );

/* Sans cette précaution, WordPress pourrait « corriger » l'adresse vers l'accueil. */
add_filter( 'redirect_canonical', function ( $url ) {
	return get_query_var( 'sueb_actus' ) ? false : $url;
} );
