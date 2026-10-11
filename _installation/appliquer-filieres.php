<?php
/**
 * Applique filieres-tutelle.php à la base : crée l'IPES s'il manque, sinon met
 * à jour son nom, son résumé, sa fiche et ses champs (ville, tutelle, filières,
 * date de signature quand elle est sûre). Utilisé par installer.php, et à
 * relancer sans risque : il ne crée jamais de doublon.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

function sueb_appliquer_filieres_tutelle( array $donnees ) {
	$journal = array();
	foreach ( $donnees as $sigle => $d ) {
		$trouve = get_posts( array( 'post_type' => 'ueb_partenaire', 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => '_sueb_sigle', 'meta_value' => $sigle ) );
		foreach ( array_filter( array( $d['titre'], $d['ancien'] ?? '' ) ) as $titre ) {
			if ( ! $trouve ) {
				$trouve = get_posts( array( 'post_type' => 'ueb_partenaire', 'post_status' => 'any', 'numberposts' => 1, 'title' => $titre ) );
			}
		}
		$champs = array(
			'ID'           => $trouve ? $trouve[0]->ID : 0,
			'post_type'    => 'ueb_partenaire',
			'post_status'  => 'publish',
			'post_title'   => $d['titre'],
			'post_excerpt' => $d['resume'],
			'post_content' => $d['texte'],
		);
		if ( ! $trouve ) {
			unset( $champs['ID'] );
		}
		$id = $trouve ? wp_update_post( $champs ) : wp_insert_post( $champs );
		$metas = array(
			'type'     => 'Établissement privé sous tutelle académique',
			'sigle'    => $sigle,
			'tutelle'  => $d['tutelle'],
			'filieres' => implode( "\n", $d['filieres'] ),
		);
		if ( $d['ville'] ) {
			$metas['ville'] = $d['ville'];
		}
		if ( $d['date'] ) {
			$metas['signature'] = $d['date'];
		}
		foreach ( $metas as $cle => $valeur ) {
			update_post_meta( $id, '_sueb_' . $cle, $valeur );
		}
		$journal[] = ( $trouve ? 'mis à jour' : 'créé' ) . " : $sigle ($id), " . count( $d['filieres'] ) . ' filières';
	}
	return $journal;
}
