<?php
/**
 * Page de connexion WordPress (/pw-ueb, voir inc/protection.php) habillée aux
 * couleurs de l'Université : bandeau « Administration », carte de connexion,
 * aucun logo, lien ni mention de WordPress.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

const SUEB_TITRE_ADMIN_WP = 'Administration de l’UEB';

add_action( 'login_enqueue_scripts', function () {
	$v = function ( $fichier ) {
		return SUEB_VERSION . '.' . ( @filemtime( SUEB_DIR . '/assets/' . $fichier ) ?: 0 );
	};
	wp_enqueue_style( 'sueb-polices', SUEB_URI . '/assets/css/polices.css', array(), $v( 'css/polices.css' ) );
	wp_enqueue_style( 'sueb-connexion-wp', SUEB_URI . '/assets/css/connexion-wp.css', array( 'login', 'sueb-polices' ), $v( 'css/connexion-wp.css' ) );
} );

/* Onglet du navigateur : « Connexion | Administration de l'UEB », sans « WordPress ». */
add_filter( 'login_title', function () {
	return 'Connexion | ' . SUEB_TITRE_ADMIN_WP;
} );

/* Logo : celui de l'Université, qui mène au site (et non à wordpress.org). */
add_filter( 'login_headerurl', function () {
	return home_url( '/' );
} );
add_filter( 'login_headertext', function () {
	return 'Université d’Ebolowa';
} );

/* Bandeau du haut. */
add_action( 'login_header', function () {
	printf(
		'<header class="sueb-pw-bandeau"><a href="%s">%s</a><span>Université d’Ebolowa</span></header>',
		esc_url( home_url( '/' ) ),
		esc_html( SUEB_TITRE_ADMIN_WP )
	);
} );

/* Ni sélecteur de langue ni « ← Aller sur Université d'Ebolowa ». */
add_filter( 'login_display_language_dropdown', '__return_false' );
add_filter( 'login_site_html_link', function () {
	return sprintf( '<a href="%s">Retour au site de l’Université</a>', esc_url( home_url( '/' ) ) );
} );

/* Identifiant inconnu ou mauvais mot de passe : un seul message, qui ne dit pas
   si le compte existe. Les autres refus (essais bloqués, compte suspendu) restent. */
add_filter( 'wp_login_errors', function ( $erreurs ) {
	if ( ! $erreurs instanceof WP_Error ) {
		return $erreurs;
	}
	$vagues = array( 'invalid_username', 'invalid_email', 'incorrect_password' );
	if ( array_intersect( $vagues, $erreurs->get_error_codes() ) ) {
		foreach ( $vagues as $code ) {
			$erreurs->remove( $code );
		}
		$erreurs->add( 'sueb_identifiants', 'Identifiant ou mot de passe incorrect.' );
	}
	return $erreurs;
} );
