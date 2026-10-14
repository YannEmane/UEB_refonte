<?php
/**
 * Deux enseignants d'exemple à la FS, rattachés aux départements d'exemple,
 * pour montrer la fiche CV. Marqués « (exemple) » et par la méta demo :
 * à supprimer dès que les vraies fiches sont saisies.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

function sueb_enseignants_exemple() {
	$fs = get_posts( array( 'post_type' => 'ueb_structure', 'post_parent' => 0, 'numberposts' => 1, 'meta_key' => '_sueb_sigle', 'meta_value' => 'FS' ) );
	if ( ! $fs || get_posts( array( 'post_type' => 'ueb_personnel', 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => '_sueb_demo', 'meta_value' => 'enseignant' ) ) ) {
		return array();
	}
	$dep = function ( $cle ) {
		$d = get_posts( array( 'post_type' => 'ueb_structure', 'numberposts' => 1, 'meta_key' => '_sueb_cle', 'meta_value' => $cle ) );
		return $d ? $d[0]->ID : 0;
	};
	$exemples = array(
		array(
			'titre' => 'Enseignant de mathématiques (exemple)',
			'corps' => 'chefs-departement',
			'bio'   => '<p>Texte d’exemple : remplacer par la biographie de l’enseignant, ses responsabilités et ses projets.</p>',
			'metas' => array(
				'fonction'     => 'Chef du département de Recherche opérationnelle et Économétrie',
				'grade'        => 'Maître de conférences',
				'departement'  => $dep( 'dep:FS-ROSE' ),
				'specialite'   => 'Mathématiques appliquées',
				'recherche'    => 'Analyse numérique, Optimisation, Modélisation mathématique',
				'cours'        => 'Analyse 1, Analyse numérique, Optimisation',
				'diplomes'     => "2012 : Doctorat en mathématiques appliquées (exemple)\n2007 : Master en mathématiques (exemple)",
				'parcours'     => "2022 - aujourd’hui : Chef de département, Faculté des Sciences, Université d’Ebolowa (exemple)\n2014-2022 : Chargé de cours (exemple)",
				'publications' => "Titre d’un article à renseigner, nom de la revue, année (exemple)\nTitre d’une communication à renseigner, colloque, année (exemple)",
			),
		),
		array(
			'titre' => 'Enseignante de biologie végétale (exemple)',
			'corps' => 'enseignants',
			'bio'   => '',
			'metas' => array(
				'fonction'     => 'Enseignante au département de Biologie et Pharmacognosie',
				'grade'        => 'Chargé de cours',
				'departement'  => $dep( 'dep:FS-BP' ),
				'specialite'   => 'Écologie végétale',
				'recherche'    => 'Forêts tropicales, Botanique',
				'cours'        => 'Botanique générale, Écologie des forêts tropicales',
				'diplomes'     => "2016 : Doctorat en biologie végétale (exemple)",
				'parcours'     => "2019 - aujourd’hui : Chargée de cours, Université d’Ebolowa (exemple)",
				'publications' => '',
			),
		),
	);
	$journal = array();
	foreach ( $exemples as $i => $e ) {
		$id = wp_insert_post( array( 'post_type' => 'ueb_personnel', 'post_status' => 'publish', 'post_title' => $e['titre'], 'post_content' => $e['bio'], 'menu_order' => 50 + $i ) );
		update_post_meta( $id, '_sueb_etablissement', $fs[0]->ID );
		update_post_meta( $id, '_sueb_demo', 'enseignant' );
		foreach ( $e['metas'] as $cle => $v ) {
			if ( '' !== $v && 0 !== $v ) {
				update_post_meta( $id, '_sueb_' . $cle, $v );
			}
		}
		wp_set_object_terms( $id, $e['corps'], 'ueb_corps' );
		$journal[] = "créé : $id " . $e['titre'];
	}
	return $journal;
}
