<?php
/**
 * Mesure d'audience du site, sans cookie ni donnée personnelle.
 *
 * Chaque page vue envoie un signal (navigator.sendBeacon, fin de assets/js/site.js) à sueb/v1/visite.
 * On compte, jour par jour, les pages vues et les visiteurs. Un visiteur est reconnu dans la journée par
 * une empreinte : le hachage de son adresse IP et de son navigateur avec un sel propre au jour (dérivé des
 * clés secrètes de wp-config.php, donc rien à enregistrer). Ni l'adresse ni le navigateur ne sont gardés ;
 * les empreintes de la veille sont effacées.
 * Ne sont pas comptés : les robots connus, les comptes connectés (l'équipe), les navigateurs qui
 * demandent à ne pas être suivis (Do Not Track).
 *
 * Stockage : deux petites tables, mises à jour par des incréments SQL atomiques (INSERT … ON DUPLICATE KEY
 * UPDATE). Deux visites simultanées ne s'écrasent donc pas, et une page vue ne réécrit pas d'option
 * WordPress sérialisée.
 *   {prefix}visites            jour, vues, visiteurs
 *   {prefix}visites_empreintes jour, empreinte, vues (celles du jour seulement)
 *
 * Le signal est public par nature (un visiteur anonyme l'envoie). Pour qu'on ne puisse pas gonfler les
 * chiffres trop facilement :
 *   - seules les requêtes venant du site lui-même sont reçues (en-tête Origin, à défaut Referer) ;
 *   - une même empreinte ne compte que SUEB_VISITES_PLAFOND pages vues par jour.
 * Ça arrête les abus simples (une boucle curl, un formulaire sur un autre site), pas quelqu'un de
 * déterminé qui change d'adresse et de navigateur à chaque envoi : ce sont des indicateurs, pas une preuve.
 *
 * Lu par le tableau de bord de la gestion (inc/tableau-de-bord.php).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/** Combien de jours de mesures on garde. */
const SUEB_VISITES_JOURS = 400;

/** Pages vues comptées au plus, par jour, pour une même empreinte (un campus derrière une seule adresse en partage une). */
const SUEB_VISITES_PLAFOND = 100;

/** Version des tables ; la changer relance dbDelta(). */
const SUEB_VISITES_VERSION = '1';

/** Les noms des deux tables. */
function sueb_visites_tables() {
	global $wpdb;
	return array( $wpdb->prefix . 'visites', $wpdb->prefix . 'visites_empreintes' );
}

/* Création des tables (et reprise des mesures enregistrées avant, dans l'option sueb_visites). */
add_action( 'init', function () {
	if ( SUEB_VISITES_VERSION === get_option( 'sueb_visites_version' ) ) {
		return;
	}
	global $wpdb;
	list( $t_jours, $t_empreintes ) = sueb_visites_tables();
	$charset = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( "CREATE TABLE {$t_jours} (
		jour date NOT NULL,
		vues int(10) unsigned NOT NULL DEFAULT 0,
		visiteurs int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (jour)
	) {$charset};" );
	dbDelta( "CREATE TABLE {$t_empreintes} (
		jour date NOT NULL,
		empreinte char(16) NOT NULL,
		vues int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (jour,empreinte)
	) {$charset};" );

	$anciennes = get_option( 'sueb_visites' );
	if ( is_array( $anciennes ) ) {
		foreach ( $anciennes as $jour => $v ) {
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $jour ) ) {
				$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$t_jours} (jour, vues, visiteurs) VALUES (%s, %d, %d)", $jour, (int) ( $v['vues'] ?? 0 ), (int) ( $v['visiteurs'] ?? 0 ) ) ); // phpcs:ignore WordPress.DB
			}
		}
	}
	delete_option( 'sueb_visites' );
	delete_option( 'sueb_visites_sel' );
	delete_option( 'sueb_visites_empreintes' );
	update_option( 'sueb_visites_version', SUEB_VISITES_VERSION );
}, 5 );

/* L'adresse du signal, pour le script du site. */
add_action( 'wp_enqueue_scripts', function () {
	if ( wp_script_is( 'sueb-site', 'enqueued' ) ) {
		wp_add_inline_script( 'sueb-site', 'window.SUEB_VISITE=' . wp_json_encode( rest_url( 'sueb/v1/visite' ) ) . ';', 'before' );
	}
}, 20 );

add_action( 'rest_api_init', function () {
	register_rest_route( 'sueb/v1', '/visite', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true', // signal public, sans donnée : voir sueb_visite_du_site() et le plafond
		'callback'            => 'sueb_compter_visite',
	) );
} );

/** Le signal vient-il d'une page du site ? (Origin, que les navigateurs envoient avec un POST ; à défaut Referer.) */
function sueb_visite_du_site( WP_REST_Request $requete ) {
	$hote   = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$origin = (string) $requete->get_header( 'origin' );
	$source = '' !== $origin ? $origin : (string) $requete->get_header( 'referer' );
	return '' !== $source && strtolower( (string) wp_parse_url( $source, PHP_URL_HOST ) ) === $hote;
}

/** Compte une page vue (et un visiteur, la première fois de la journée). */
function sueb_compter_visite( WP_REST_Request $requete ) {
	$navigateur = (string) $requete->get_header( 'user_agent' );
	if ( '' === $navigateur || preg_match( '/bot|crawl|spider|slurp|preview|headless|lighthouse|curl|wget|python|httpclient|facebookexternalhit|whatsapp/i', $navigateur ) ) {
		return new WP_REST_Response( null, 204 );
	}
	if ( ! sueb_visite_du_site( $requete ) ) {
		return new WP_REST_Response( null, 204 ); // même réponse : rien à apprendre pour qui essaie
	}

	global $wpdb;
	list( $t_jours, $t_empreintes ) = sueb_visites_tables();
	$jour      = wp_date( 'Y-m-d' );
	$ip        = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$sel       = hash_hmac( 'sha256', 'visites|' . $jour, wp_salt( 'nonce' ) );
	$empreinte = substr( hash( 'sha256', $sel . '|' . $ip . '|' . $navigateur ), 0, 16 );

	/* 1 ligne touchée : nouvelle empreinte du jour ; 2 : déjà vue, son compteur augmente. */
	$touchees = $wpdb->query( $wpdb->prepare( "INSERT INTO {$t_empreintes} (jour, empreinte, vues) VALUES (%s, %s, 1) ON DUPLICATE KEY UPDATE vues = vues + 1", $jour, $empreinte ) ); // phpcs:ignore WordPress.DB
	if ( false === $touchees ) {
		return new WP_REST_Response( null, 204 );
	}
	$nouveau = 1 === (int) $touchees;
	if ( ! $nouveau ) {
		$vues = (int) $wpdb->get_var( $wpdb->prepare( "SELECT vues FROM {$t_empreintes} WHERE jour = %s AND empreinte = %s", $jour, $empreinte ) ); // phpcs:ignore WordPress.DB
		if ( $vues > SUEB_VISITES_PLAFOND ) {
			return new WP_REST_Response( null, 204 );
		}
	}

	$touchees = $wpdb->query( $wpdb->prepare( "INSERT INTO {$t_jours} (jour, vues, visiteurs) VALUES (%s, 1, %d) ON DUPLICATE KEY UPDATE vues = vues + 1, visiteurs = visiteurs + %d", $jour, $nouveau ? 1 : 0, $nouveau ? 1 : 0 ) ); // phpcs:ignore WordPress.DB

	/* Premier signal du jour : on efface les empreintes de la veille et les mesures trop anciennes. */
	if ( 1 === (int) $touchees ) {
		$limite = ( new DateTimeImmutable( $jour, wp_timezone() ) )->modify( '-' . SUEB_VISITES_JOURS . ' day' )->format( 'Y-m-d' );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$t_empreintes} WHERE jour < %s", $jour ) ); // phpcs:ignore WordPress.DB
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$t_jours} WHERE jour < %s", $limite ) ); // phpcs:ignore WordPress.DB
	}
	return new WP_REST_Response( null, 204 );
}

/** Le premier jour mesuré (AAAA-MM-JJ), ou ''. */
function sueb_visites_depuis() {
	global $wpdb;
	list( $t_jours ) = sueb_visites_tables();
	return (string) $wpdb->get_var( "SELECT MIN(jour) FROM {$t_jours}" ); // phpcs:ignore WordPress.DB
}

/**
 * Les visites des derniers jours, jour par jour (les jours sans visite valent 0), du plus ancien au plus
 * récent : array( array( 'jour' => 'AAAA-MM-JJ', 'vues' => n, 'visiteurs' => n ), … ).
 */
function sueb_visites_recentes( $jours = 30 ) {
	global $wpdb;
	list( $t_jours ) = sueb_visites_tables();
	$fuseau  = wp_timezone();
	$debut   = ( new DateTimeImmutable( 'now', $fuseau ) )->modify( '-' . ( $jours - 1 ) . ' day' )->format( 'Y-m-d' );
	$visites = array();
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT jour, vues, visiteurs FROM {$t_jours} WHERE jour >= %s", $debut ), ARRAY_A ) as $l ) { // phpcs:ignore WordPress.DB
		$visites[ $l['jour'] ] = $l;
	}
	$liste = array();
	for ( $i = $jours - 1; $i >= 0; $i-- ) {
		$jour    = ( new DateTimeImmutable( 'now', $fuseau ) )->modify( "-$i day" )->format( 'Y-m-d' );
		$liste[] = array(
			'jour'      => $jour,
			'vues'      => (int) ( $visites[ $jour ]['vues'] ?? 0 ),
			'visiteurs' => (int) ( $visites[ $jour ]['visiteurs'] ?? 0 ),
		);
	}
	return $liste;
}
