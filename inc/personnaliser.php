<?php
/**
 * Réglages du site : page d'accueil (bannière, message, frise, vidéo) et
 * coordonnées / liens de l'université. Une seule définition (sueb_reglages_def)
 * sert au Personnaliseur (Apparence > Personnaliser) et à la page de gestion
 * (/gestion/, par la route REST sueb/v1/reglages de inc/admin.php).
 * Les valeurs sont des « theme mods ».
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les réglages, par section : clé => array( libellé, type, défaut, aide ).
 * Types : text, textarea, url, email, image, video (ID de pièce jointe).
 */
function sueb_reglages_def() {
	return array(
		'accueil'     => array(
			'titre'    => 'Accueil UEb',
			'reglages' => array(
				'accueil_surtitre' => array( 'Bannière : surtitre', 'text', 'Université d’État · Créée en 2022' ),
				'accueil_titre'    => array( 'Bannière : titre', 'text', 'Le savoir au cœur de la forêt équatoriale' ),
				'accueil_texte'    => array( 'Bannière : texte', 'textarea', 'Neuf établissements à Ebolowa, Sangmélima, Ambam et Kribi pour former, chercher et innover au service du Sud et du Cameroun.' ),
				'recteur_citation' => array( 'Message du Recteur : citation d’accueil', 'textarea', 'Bienvenue à l’Université d’Ebolowa.' ),
				'arrete'           => array( 'Histoire : référence de l’arrêté ministériel', 'text', '', 'Utilisée par la frise par défaut, tant que la frise ci-dessous n’est pas personnalisée.' ),
				'frise'            => array( 'Histoire : frise chronologique', 'textarea', '', 'Une étape par ligne : « date | étiquette | texte » (ex. « 4 août 2022 | Installation | Installation du Recteur… »). Vider le champ pour revenir à la frise par défaut.' ),
				'video_titre'      => array( 'Vidéo de présentation : titre', 'text', 'L’Université d’Ebolowa en images' ),
				'video_texte'      => array( 'Vidéo de présentation : texte', 'textarea', 'Campus, établissements, vie étudiante : découvrez l’UEb en quelques minutes.' ),
				'video_url'        => array( 'Vidéo de présentation : lien YouTube ou Vimeo', 'url', '', 'Sinon, le fichier vidéo ci-dessous.' ),
				'video_fichier'    => array( 'Vidéo de présentation : fichier MP4', 'video', '' ),
				'video_affiche'    => array( 'Vidéo de présentation : image d’affiche', 'image', '' ),
			),
		),
		'coordonnees' => array(
			'titre'    => 'Coordonnées et liens UEb',
			'reglages' => array(
				'univ_bp'             => array( 'Boîte postale', 'text', SUEB_UNIVERSITE['bp'] ),
				'univ_ville'          => array( 'Ville et région', 'text', SUEB_UNIVERSITE['ville'] ),
				'univ_tel'            => array( 'Téléphone', 'text', SUEB_UNIVERSITE['tel'] ),
				'univ_email'          => array( 'Courriel', 'email', SUEB_UNIVERSITE['email'] ),
				'lien_preinscription' => array( 'Plateforme de préinscription', 'url', SUEB_LIENS['preinscription'] ),
				'lien_inscription'    => array( 'Plateforme d’inscription administrative', 'url', SUEB_LIENS['inscription'] ),
				'lien_facebook'       => array( 'Page Facebook', 'url', SUEB_LIENS['facebook'] ),
				'lien_youtube'        => array( 'Chaîne YouTube', 'url', SUEB_LIENS['youtube'] ),
			),
		),
	);
}

/** Tous les réglages à plat : clé => définition. */
function sueb_reglages_tous() {
	$tous = array();
	foreach ( sueb_reglages_def() as $section ) {
		$tous += $section['reglages'];
	}
	return $tous;
}

/** Valeurs par défaut des réglages. */
function sueb_reglages_defaut() {
	return wp_list_pluck( sueb_reglages_tous(), 2 );
}

function sueb_reglage( $cle ) {
	$defaut = sueb_reglages_defaut();
	$v      = get_theme_mod( $cle, $defaut[ $cle ] ?? '' );
	/* Une coordonnée ou un lien vidé reprend sa valeur d'origine plutôt que de laisser un trou dans le pied de page. */
	if ( '' === $v && preg_match( '/^(univ|lien)_/', $cle ) ) {
		$v = $defaut[ $cle ] ?? '';
	}
	return $v;
}

/** Nettoyage d'une valeur de réglage selon son type. */
function sueb_nettoyer_reglage( $v, $type ) {
	$v = is_scalar( $v ) ? (string) $v : '';
	switch ( $type ) {
		case 'image':
		case 'video':
			return absint( $v ) ?: '';
		case 'url':
			return esc_url_raw( $v );
		case 'email':
			return sanitize_email( $v );
		case 'textarea':
			return sanitize_textarea_field( $v );
		default:
			return sanitize_text_field( $v );
	}
}

/** Identité de l'université : SUEB_UNIVERSITE, avec les coordonnées réglées dans la gestion. */
function sueb_universite() {
	$u = SUEB_UNIVERSITE;
	foreach ( array( 'bp', 'ville', 'tel', 'email' ) as $cle ) {
		$u[ $cle ] = sueb_reglage( 'univ_' . $cle );
	}
	return $u;
}

/** Lien vers une plateforme ou un réseau (preinscription, inscription, facebook, youtube). */
function sueb_lien( $cle ) {
	return sueb_reglage( 'lien_' . $cle );
}

/** Frise par défaut de la section Histoire : array( date, étiquette, texte ). */
function sueb_jalons_defaut( $avec_rappel = false ) {
	$arrete = sueb_reglage( 'arrete' );
	$jalons = array(
		array( '6 janvier 2022', 'Création', 'Le décret présidentiel n° 2022/009 crée l’Université d’Ebolowa, université d’État de la Région du Sud.' ),
	);
	if ( $arrete ) {
		$jalons[] = array( 'Arrêté ministériel', 'Organisation', $arrete );
	} elseif ( $avec_rappel ) {
		$jalons[] = array( 'Arrêté ministériel', 'À compléter', 'Référence et objet de l’arrêté à saisir dans la gestion du site › Accueil (visible des seuls éditeurs).' );
	}
	$jalons[] = array( '4 août 2022', 'Installation', 'Installation du Recteur et du Président du Conseil d’administration ; l’université commence ses activités.' );
	$jalons[] = array( '18 novembre 2024', 'Ouverture', 'Convention-cadre de partenariat avec le Port Autonome de Kribi.' );
	$jalons[] = array( '2026', 'Rayonnement', 'Nouvelles conventions, dont le ministère du Tourisme et des Loisirs et l’Institut Sapientia.' );
	return $jalons;
}

/** Les jalons de la frise : ceux saisis dans le réglage « frise », sinon la frise par défaut. */
function sueb_jalons( $avec_rappel = false ) {
	$jalons = array();
	foreach ( preg_split( '/\R/', (string) sueb_reglage( 'frise' ) ) as $ligne ) {
		$p = array_map( 'trim', explode( '|', $ligne, 3 ) );
		if ( '' !== $p[0] ) {
			$jalons[] = array( $p[0], $p[1] ?? '', $p[2] ?? '' );
		}
	}
	return $jalons ?: sueb_jalons_defaut( $avec_rappel );
}

/** La frise par défaut, au format du réglage (pour pré-remplir le champ). */
function sueb_frise_texte_defaut() {
	return implode( "\n", array_map( function ( $j ) {
		return implode( ' | ', $j );
	}, sueb_jalons_defaut() ) );
}

add_action( 'customize_register', function ( WP_Customize_Manager $c ) {
	foreach ( sueb_reglages_def() as $id => $section ) {
		$c->add_section( 'sueb_' . $id, array( 'title' => $section['titre'], 'priority' => 30 ) );
		foreach ( $section['reglages'] as $cle => $r ) {
			$type = $r[1];
			$c->add_setting( $cle, array(
				'default'           => $r[2],
				'sanitize_callback' => function ( $v ) use ( $type ) {
					return sueb_nettoyer_reglage( $v, $type );
				},
			) );
			$args = array(
				'label'       => $r[0],
				'description' => $r[3] ?? '',
				'section'     => 'sueb_' . $id,
			);
			if ( in_array( $type, array( 'image', 'video' ), true ) ) {
				$c->add_control( new WP_Customize_Media_Control( $c, $cle, $args + array( 'mime_type' => $type ) ) );
			} else {
				$c->add_control( $cle, $args + array( 'type' => $type ) );
			}
		}
	}
} );
