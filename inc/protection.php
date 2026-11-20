<?php
/**
 * Protection du site en ligne : connexion WordPress à une adresse propre au
 * site (/pw-ueb), wp-login.php et wp-admin introuvables pour un visiteur,
 * comptes WordPress cachés, XML-RPC coupé.
 * Même principe que la plateforme des inscriptions (inc/protection.php).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Connexion WordPress à une adresse propre au site ---------- */

/* La page de connexion de WordPress ne répond plus qu'à /pw-ueb ; wp-login.php
   et wp-admin, pour un visiteur non connecté, se comportent comme une adresse
   inconnue (page 404 du thème) au lieu de mener à la connexion. Le dossier
   wp-admin ne peut pas être renommé (WordPress y renvoie en dur) : une fois
   connecté, l'administrateur y travaille comme avant. admin-ajax.php et
   admin-post.php restent ouverts (connexion de la page /gestion/). */
const SUEB_SLUG_CONNEXION_WP = 'pw-ueb';

/** Adresse de la connexion WordPress. */
function sueb_url_connexion_wp( $requete = '' ) {
	$url = home_url( '/' . SUEB_SLUG_CONNEXION_WP . '/' );
	return '' === $requete ? $url : $url . '?' . $requete;
}

/** Chemin demandé, sans le dossier du site ni la requête, sans « / » aux bouts. */
function sueb_chemin_demande() {
	$chemin = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- seulement comparé
	$base   = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( '' !== $base && 0 === strpos( $chemin, $base ) ) {
		$chemin = substr( $chemin, strlen( $base ) );
	}
	return trim( rawurldecode( $chemin ), '/' );
}

/** Réponse d'une adresse inconnue (page 404 du thème), sans rien révéler de WordPress. */
function sueb_repondre_introuvable() {
	global $wp_query;
	status_header( 404 );
	nocache_headers();
	if ( $wp_query instanceof WP_Query ) {
		$wp_query->set_404();
	}
	$gabarit = get_404_template() ?: get_index_template();
	if ( $gabarit ) {
		include $gabarit;
	}
	exit;
}

/** Remplace wp-login.php par l'adresse propre au site dans une URL produite par WordPress. */
function sueb_url_sans_wp_login( $url ) {
	if ( ! is_string( $url ) || false === strpos( $url, 'wp-login.php' ) ) {
		return $url;
	}
	return sueb_url_connexion_wp( (string) wp_parse_url( $url, PHP_URL_QUERY ) );
}
foreach ( array( 'site_url', 'network_site_url', 'wp_redirect', 'login_url', 'logout_url', 'lostpassword_url', 'register_url' ) as $sueb_filtre ) {
	add_filter( $sueb_filtre, 'sueb_url_sans_wp_login', 100 );
}
unset( $sueb_filtre );

/* /login, /admin, /dashboard ne renvoient plus vers la connexion. */
remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );

add_action( 'wp_loaded', function () {
	if ( 'cli' === PHP_SAPI || wp_doing_cron() || wp_doing_ajax() ) {
		return;
	}
	global $pagenow;
	$chemin = sueb_chemin_demande();

	/* /pw-ueb : la page de connexion de WordPress, servie à cette adresse. */
	if ( SUEB_SLUG_CONNEXION_WP === $chemin ) {
		global $error, $interim_login, $action, $user_login, $user, $redirect_to; // variables lues par wp-login.php
		$pagenow = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		require_once ABSPATH . 'wp-login.php';
		exit;
	}

	/* wp-login.php appelé directement : adresse inconnue. */
	if ( 'wp-login.php' === $pagenow || preg_match( '#(^|/)wp-login\.php$#', $chemin ) ) {
		sueb_repondre_introuvable();
	}

	/* XML-RPC et fichiers publics du cœur dont le site n'a pas l'usage : adresse inconnue
	   (sans quoi ils répondent par des messages propres à WordPress). */
	if ( defined( 'XMLRPC_REQUEST' ) || preg_match( '#(^|/)(xmlrpc|wp-links-opml|wp-trackback|wp-signup|wp-activate|wp-mail|wp-comments-post)\.php$#', $chemin ) ) {
		sueb_repondre_introuvable();
	}

	/* wp-admin pour un visiteur non connecté : adresse inconnue, plutôt qu'un renvoi vers la connexion. */
	if ( is_admin() && ! is_user_logged_in() && 'admin-post.php' !== $pagenow ) {
		sueb_repondre_introuvable();
	}
}, 1 );

/* ---------- Comptes et portes inutiles ---------- */

/* La liste des comptes WordPress (/wp-json/wp/v2/users) n'est lisible que connecté. */
add_filter( 'rest_endpoints', function ( $routes ) {
	if ( ! is_user_logged_in() ) {
		foreach ( array_keys( $routes ) as $route ) {
			if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
				unset( $routes[ $route ] );
			}
		}
	}
	return $routes;
} );

/* ?author=1 et /author/… donnent l'identifiant de connexion : retour à l'accueil. */
add_action( 'template_redirect', function () {
	if ( is_author() || isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- simple renvoi
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}, 1 );

add_filter( 'oembed_response_data', function ( $donnees ) {
	unset( $donnees['author_name'], $donnees['author_url'] );
	return $donnees;
} );

add_filter( 'wp_sitemaps_add_provider', function ( $fournisseur, $nom ) {
	return 'users' === $nom ? false : $fournisseur;
}, 10, 2 );

/* XML-RPC : le site n'en a pas l'usage, et il permet d'essayer des milliers
   de mots de passe en une seule requête. */
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
/* Mots de passe d'application : une porte de connexion de plus, inutile ici. */
add_filter( 'wp_is_application_passwords_available', '__return_false' );
remove_action( 'wp_head', 'rsd_link' );
add_filter( 'wp_headers', function ( $entetes ) {
	unset( $entetes['X-Pingback'] );
	return $entetes;
} );

/* Générateur (« WordPress 7.1.4 ») : ni dans les pages, ni dans les flux. */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );
