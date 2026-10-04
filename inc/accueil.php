<?php
/**
 * Données de la page d'accueil, lues dans les contenus de l'admin.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/** Une page par son identifiant d'URL (message-du-recteur, histoire…). */
function sueb_page( $slug ) {
	return get_page_by_path( $slug );
}

/** Les dirigeants du rectorat, dans l'ordre choisi dans l'admin. */
function sueb_dirigeants() {
	$officiels = sueb_fonctions_rectorat();
	$liste     = array();
	foreach ( get_posts( array( 'post_type' => 'ueb_dirigeant', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) as $p ) {
		$sigle   = (string) sueb_meta( $p->ID, 'sigle' );
		$liste[] = array(
			'id'       => $p->ID,
			'nom'      => $p->post_title,
			'sigle'    => 'RECTEUR' === $sigle ? '' : $sigle,
			'fonction' => sueb_meta( $p->ID, 'fonction' ) ?: ( $officiels[ $sigle ] ?? '' ),
			'photo'    => sueb_image_url( $p->ID, 'sueb-portrait' ),
			'recteur'  => 'RECTEUR' === $sigle,
		);
	}
	return $liste;
}

/**
 * L'offre de formation en arbre : établissements > départements >
 * filières > UE. Une seule requête, l'arbre est monté en mémoire.
 */
function sueb_arbre_formation() {
	$tous = get_posts( array( 'post_type' => 'ueb_structure', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
	$fixe = sueb_etablissements();
	$noeuds = array();
	foreach ( $tous as $p ) {
		$sigle = (string) sueb_meta( $p->ID, 'sigle' );
		$n     = array(
			'id'     => $p->ID,
			'parent' => $p->post_parent,
			'titre'  => $p->post_title,
			'sigle'  => $sigle,
			'url'    => get_permalink( $p ),
			'texte'  => wp_strip_all_tags( get_the_excerpt( $p ) ),
			'resp'   => (string) sueb_meta( $p->ID, 'responsable' ),
			'enfants'=> array(),
		);
		if ( 0 === $p->post_parent ) {
			$f            = $fixe[ strtoupper( $sigle ) ] ?? array( 'couleur' => '#163d2b', 'ville' => '', 'photos' => array( 'campus-ebolowa', 'amphi' ) );
			$logo         = (int) sueb_meta( $p->ID, 'logo' );
			$n['ville']   = sueb_meta( $p->ID, 'ville' ) ?: $f['ville'];
			$n['couleur'] = sueb_meta( $p->ID, 'couleur' ) ?: $f['couleur'];
			$n['logo']    = $logo ? wp_get_attachment_image_url( $logo, 'medium' ) : sueb_logo( $sigle );
			$n['photo_g'] = sueb_image_url( $p->ID, 'sueb-carte', '', $f['photos'][0] );
			$n['photo_d'] = sueb_image_url( $p->ID, 'sueb-carte', 'photo_droite', $f['photos'][1] );
		} else {
			$syllabus     = (int) sueb_meta( $p->ID, 'syllabus' );
			$n['code']    = (string) sueb_meta( $p->ID, 'code' );
			$n['credits'] = (string) sueb_meta( $p->ID, 'credits' );
			$n['semestre']= (string) sueb_meta( $p->ID, 'semestre' );
			$n['volume']  = (string) sueb_meta( $p->ID, 'volume' );
			$n['diplome'] = (string) sueb_meta( $p->ID, 'diplome' );
			$n['syllabus']= $syllabus ? (string) wp_get_attachment_url( $syllabus ) : '';
		}
		$noeuds[ $p->ID ] = $n;
	}

	/* Montage de l'arbre par références, des feuilles vers la racine. */
	$racines = array();
	foreach ( $noeuds as $id => &$n ) {
		if ( $n['parent'] && isset( $noeuds[ $n['parent'] ] ) ) {
			$noeuds[ $n['parent'] ]['enfants'][] = &$n;
		} elseif ( ! $n['parent'] ) {
			$racines[] = &$n;
		}
	}
	unset( $n );
	return $racines;
}

/** Nombre de structures d'un niveau sous un nœud (départements, filières, UE). */
function sueb_compter( array $noeud, $profondeur ) {
	if ( 0 === $profondeur ) {
		return 1;
	}
	$total = 0;
	foreach ( $noeud['enfants'] as $e ) {
		$total += sueb_compter( $e, $profondeur - 1 );
	}
	return $total;
}

function sueb_partenaires( $nombre = 12 ) {
	$liste = array();
	foreach ( get_posts( array( 'post_type' => 'ueb_partenaire', 'numberposts' => $nombre, 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'DESC' ) ) ) as $p ) {
		$logo    = (int) sueb_meta( $p->ID, 'logo' );
		$liste[] = array(
			'titre'     => $p->post_title,
			'type'      => sueb_meta( $p->ID, 'type' ),
			'texte'     => wp_strip_all_tags( get_the_excerpt( $p ) ),
			'photo'     => sueb_image_url( $p->ID, 'sueb-carte' ),
			'logo'      => $logo ? wp_get_attachment_image_url( $logo, 'medium' ) : '',
			'signature' => sueb_meta( $p->ID, 'signature' ),
			'url'       => get_permalink( $p ),
			'lien'      => sueb_meta( $p->ID, 'lien' ),
		);
	}
	return $liste;
}

/** Les dernières actualités, avec leur format (article, vidéo, audio) et leur média. */
function sueb_actualites( $nombre = 7 ) {
	$liste = array();
	foreach ( get_posts( array( 'post_type' => 'post', 'numberposts' => $nombre ) ) as $p ) {
		$format = get_post_format( $p ) ?: 'article';
		$media  = (int) sueb_meta( $p->ID, 'media' );
		$cats   = get_the_category( $p->ID );
		$liste[] = array(
			'titre'    => $p->post_title,
			'url'      => get_permalink( $p ),
			'date'     => get_the_date( 'j M Y', $p ),
			'iso'      => get_the_date( 'c', $p ),
			'texte'    => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $p ) ), 28 ),
			'photo'    => sueb_image_url( $p->ID, 'sueb-carte', '', 'campus-ebolowa' ),
			'format'   => in_array( $format, array( 'video', 'audio' ), true ) ? $format : 'article',
			'media'    => $media ? (string) wp_get_attachment_url( $media ) : '',
			'integre'  => sueb_video_integree( (string) sueb_meta( $p->ID, 'media_url' ) ),
			'duree'    => sueb_meta( $p->ID, 'duree' ),
			'rubrique' => $cats && 'uncategorized' !== $cats[0]->slug && 'non-classe' !== $cats[0]->slug ? $cats[0]->name : '',
		);
	}
	return $liste;
}

/** Les évènements à venir (puis les plus récents s'il n'y en a pas assez). */
function sueb_evenements( $nombre = 4 ) {
	$args = array(
		'post_type'   => 'ueb_evenement',
		'numberposts' => $nombre,
		'meta_key'    => '_sueb_debut',
		'orderby'     => 'meta_value',
		'order'       => 'ASC',
		'meta_query'  => array( array( 'key' => '_sueb_debut', 'value' => current_time( 'Y-m-d\TH:i' ), 'compare' => '>=' ) ),
	);
	$posts = get_posts( $args );
	if ( count( $posts ) < $nombre ) {
		unset( $args['meta_query'] );
		$args['order']       = 'DESC';
		$args['numberposts'] = $nombre - count( $posts );
		$args['exclude']     = wp_list_pluck( $posts, 'ID' );
		$posts               = array_merge( $posts, get_posts( $args ) );
	}
	$liste = array();
	foreach ( $posts as $p ) {
		$debut   = strtotime( (string) sueb_meta( $p->ID, 'debut' ) );
		$liste[] = array(
			'titre'  => $p->post_title,
			'url'    => get_permalink( $p ),
			'jour'   => $debut ? wp_date( 'j', $debut ) : '',
			'mois'   => $debut ? wp_date( 'M', $debut ) : '',
			'heure'  => $debut ? wp_date( 'G\hi', $debut ) : '',
			'iso'    => $debut ? wp_date( 'c', $debut ) : '',
			'passe'  => $debut && $debut < current_time( 'timestamp' ),
			'lieu'   => sueb_meta( $p->ID, 'lieu' ),
		);
	}
	return $liste;
}

/** Le personnel, groupé par catégorie (dans l'ordre des catégories). */
function sueb_personnel_par_corps() {
	$sigles = array();
	foreach ( get_posts( array( 'post_type' => 'ueb_structure', 'post_parent' => 0, 'numberposts' => -1 ) ) as $e ) {
		$sigles[ $e->ID ] = sueb_meta( $e->ID, 'sigle' ) ?: $e->post_title;
	}
	$groupes = array();
	$corps   = get_terms( array( 'taxonomy' => 'ueb_corps', 'hide_empty' => false, 'orderby' => 'term_id' ) );
	foreach ( is_array( $corps ) ? $corps : array() as $t ) {
		$membres = array();
		foreach ( get_posts( array( 'post_type' => 'ueb_personnel', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'tax_query' => array( array( 'taxonomy' => 'ueb_corps', 'terms' => $t->term_id ) ) ) ) as $p ) {
			$etab      = (int) sueb_meta( $p->ID, 'etablissement' );
			$membres[] = array(
				'nom'      => $p->post_title,
				'fonction' => sueb_meta( $p->ID, 'fonction' ),
				'cours'    => sueb_meta( $p->ID, 'cours' ),
				'etab'     => $etab && isset( $sigles[ $etab ] ) ? $sigles[ $etab ] : 'Rectorat',
				'photo'    => sueb_image_url( $p->ID, 'sueb-portrait' ),
				'url'      => get_permalink( $p ),
			);
		}
		$groupes[] = array( 'nom' => $t->name, 'slug' => $t->slug, 'membres' => $membres );
	}
	return $groupes;
}
