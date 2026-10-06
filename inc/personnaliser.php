<?php
/**
 * Réglages de la page d'accueil (Apparence > Personnaliser > Accueil UEb).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/** Valeurs par défaut des réglages. */
function sueb_reglages_defaut() {
	return array(
		'accueil_surtitre'   => 'Université d’État · Créée en 2022',
		'accueil_titre'      => 'Le savoir au cœur de la forêt équatoriale',
		'accueil_texte'      => 'Neuf établissements à Ebolowa, Sangmélima, Ambam et Kribi pour former, chercher et innover au service du Sud et du Cameroun.',
		'recteur_citation'   => 'Bienvenue à l’Université d’Ebolowa.',
		'video_titre'        => 'L’Université d’Ebolowa en images',
		'video_texte'        => 'Campus, établissements, vie étudiante : découvrez l’UEb en quelques minutes.',
		'arrete'             => '',
	);
}

function sueb_reglage( $cle ) {
	$defaut = sueb_reglages_defaut();
	return get_theme_mod( $cle, $defaut[ $cle ] ?? '' );
}

add_action( 'customize_register', function ( WP_Customize_Manager $c ) {
	$c->add_section( 'sueb_accueil', array( 'title' => 'Accueil UEb', 'priority' => 30 ) );
	$d = sueb_reglages_defaut();

	$textes = array(
		'accueil_surtitre' => array( 'Bannière : surtitre', 'text' ),
		'accueil_titre'    => array( 'Bannière : titre', 'text' ),
		'accueil_texte'    => array( 'Bannière : texte', 'textarea' ),
		'recteur_citation' => array( 'Message du Recteur : citation d’accueil', 'textarea' ),
		'arrete'           => array( 'Histoire : référence de l’arrêté ministériel', 'text' ),
		'video_titre'      => array( 'Vidéo de présentation : titre', 'text' ),
		'video_texte'      => array( 'Vidéo de présentation : texte', 'textarea' ),
		'video_url'        => array( 'Vidéo de présentation : lien YouTube ou Vimeo (sinon le fichier ci-dessous)', 'url' ),
	);
	foreach ( $textes as $cle => $t ) {
		$c->add_setting( $cle, array( 'default' => $d[ $cle ] ?? '', 'sanitize_callback' => 'url' === $t[1] ? 'esc_url_raw' : ( 'textarea' === $t[1] ? 'sanitize_textarea_field' : 'sanitize_text_field' ) ) );
		$c->add_control( $cle, array( 'label' => $t[0], 'section' => 'sueb_accueil', 'type' => $t[1] ) );
	}

	foreach ( array( 'video_fichier' => array( 'Vidéo de présentation : fichier MP4', 'video' ), 'video_affiche' => array( 'Vidéo de présentation : image d’affiche', 'image' ) ) as $cle => $t ) {
		$c->add_setting( $cle, array( 'sanitize_callback' => 'absint' ) );
		$c->add_control( new WP_Customize_Media_Control( $c, $cle, array( 'label' => $t[0], 'section' => 'sueb_accueil', 'mime_type' => $t[1] ) ) );
	}
} );
