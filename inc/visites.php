<?php
/**
 * Mesure d'audience du site, sans cookie ni donnée personnelle.
 *
 * Chaque page vue envoie un signal (navigator.sendBeacon, fin de assets/js/site.js) à sueb/v1/visite.
 * On compte, jour par jour, les pages vues et les visiteurs. Un visiteur est reconnu dans la journée par
 * une empreinte : le hachage de son adresse IP et de son navigateur avec un sel qui change chaque jour.
 * Ni l'adresse ni le navigateur ne sont enregistrés ; les empreintes de la veille sont effacées.
 * Ne sont pas comptés : les robots connus, les comptes connectés (l'équipe), les navigateurs qui
 * demandent à ne pas être suivis (Do Not Track).
 *
 * Lu par le tableau de bord de la gestion (inc/tableau-de-bord.php).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/** Combien de jours de mesures on garde. */
const SUEB_VISITES_JOURS = 400;

/** Les mesures : array( 'AAAA-MM-JJ' => array( 'vues' => n, 'visiteurs' => n ) ). */
function sueb_visites() {
	return (array) get_option( 'sueb_visites', array() );
}

/* L'adresse du signal, pour le script du site. */
add_action( 'wp_enqueue_scripts', function () {
	if ( wp_script_is( 'sueb-site', 'enqueued' ) ) {
		wp_add_inline_script( 'sueb-site', 'window.SUEB_VISITE=' . wp_json_encode( rest_url( 'sueb/v1/visite' ) ) . ';', 'before' );
	}
}, 20 );

add_action( 'rest_api_init', function () {
	register_rest_route( 'sueb/v1', '/visite', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true', // signal public, sans donnée
		'callback'            => 'sueb_compter_visite',
	) );
} );

/** Compte une page vue (et un visiteur, la première fois de la journée). */
function sueb_compter_visite( WP_REST_Request $requete ) {
	$navigateur = (string) $requete->get_header( 'user_agent' );
	if ( '' === $navigateur || preg_match( '/bot|crawl|spider|slurp|preview|headless|lighthouse|curl|wget|python|httpclient|facebookexternalhit|whatsapp/i', $navigateur ) ) {
		return new WP_REST_Response( null, 204 );
	}
	$jour = wp_date( 'Y-m-d' );

	/* Le sel du jour ; un nouveau jour efface les empreintes de la veille. */
	$sel = get_option( 'sueb_visites_sel' );
	if ( ! is_array( $sel ) || ( $sel['jour'] ?? '' ) !== $jour ) {
		$sel = array( 'jour' => $jour, 'sel' => wp_generate_password( 32, true, true ) );
		update_option( 'sueb_visites_sel', $sel, false );
		update_option( 'sueb_visites_empreintes', array(), false );
	}
	$ip         = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$empreinte  = substr( hash( 'sha256', $sel['sel'] . '|' . $ip . '|' . $navigateur ), 0, 16 );
	$empreintes = (array) get_option( 'sueb_visites_empreintes', array() );
	$nouveau    = ! isset( $empreintes[ $empreinte ] );
	if ( $nouveau && count( $empreintes ) < 50000 ) {
		$empreintes[ $empreinte ] = 1;
		update_option( 'sueb_visites_empreintes', $empreintes, false );
	}

	$visites          = sueb_visites();
	$visites[ $jour ] = array(
		'vues'      => (int) ( $visites[ $jour ]['vues'] ?? 0 ) + 1,
		'visiteurs' => (int) ( $visites[ $jour ]['visiteurs'] ?? 0 ) + ( $nouveau ? 1 : 0 ),
	);
	if ( count( $visites ) > SUEB_VISITES_JOURS ) {
		ksort( $visites );
		$visites = array_slice( $visites, -SUEB_VISITES_JOURS, null, true );
	}
	update_option( 'sueb_visites', $visites, false );
	return new WP_REST_Response( null, 204 );
}

/**
 * Les visites des derniers jours, jour par jour (les jours sans visite valent 0), du plus ancien au plus
 * récent : array( array( 'jour' => 'AAAA-MM-JJ', 'vues' => n, 'visiteurs' => n ), … ).
 */
function sueb_visites_recentes( $jours = 30 ) {
	$visites = sueb_visites();
	$liste   = array();
	$fuseau  = wp_timezone();
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
