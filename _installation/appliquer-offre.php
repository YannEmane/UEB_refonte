<?php
/**
 * Charge offre-formation.php dans la base : départements et filières de chaque
 * établissement, avec leur diplôme et leurs niveaux. Retire d'abord les
 * structures d'exemple (méta demo). À relancer sans risque : chaque
 * département et chaque filière est retrouvé par sa clé (méta « cle »,
 * « dep:FS-GE » ou « fil:FS-GE » : un même code peut désigner l'un et l'autre).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

function sueb_appliquer_offre_formation( array $offre ) {
	$journal = array();
	$etabs   = array();
	foreach ( get_posts( array( 'post_type' => 'ueb_structure', 'post_parent' => 0, 'numberposts' => -1, 'post_status' => 'any' ) ) as $e ) {
		$etabs[ strtoupper( (string) get_post_meta( $e->ID, '_sueb_sigle', true ) ) ] = $e->ID;
	}

	/* Les structures d'exemple laissent la place à la vraie offre. */
	$exemples = get_posts( array( 'post_type' => 'ueb_structure', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => '_sueb_demo', 'meta_value' => '1' ) );
	foreach ( $exemples as $x ) {
		wp_delete_post( $x->ID, true );
	}
	if ( $exemples ) {
		$journal[] = count( $exemples ) . ' structures d’exemple supprimées';
	}

	$trouver = function ( $cle ) {
		$p = get_posts( array( 'post_type' => 'ueb_structure', 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => '_sueb_cle', 'meta_value' => $cle ) );
		return $p ? $p[0]->ID : 0;
	};
	$enregistrer = function ( $cle, array $champs, array $metas ) use ( $trouver ) {
		$id     = $trouver( $cle );
		$champs = array_merge( array( 'post_type' => 'ueb_structure', 'post_status' => 'publish' ), $champs );
		if ( $id ) {
			$champs['ID'] = $id;
			wp_update_post( $champs );
		} else {
			$id = wp_insert_post( $champs );
		}
		update_post_meta( $id, '_sueb_cle', $cle );
		foreach ( $metas as $k => $v ) {
			if ( '' === $v || false === $v ) {
				delete_post_meta( $id, '_sueb_' . $k );
			} else {
				update_post_meta( $id, '_sueb_' . $k, $v );
			}
		}
		return $id;
	};

	$deps = array();
	foreach ( $offre['departements'] as $sigle => $liste ) {
		if ( empty( $etabs[ $sigle ] ) ) {
			$journal[] = "établissement introuvable : $sigle";
			continue;
		}
		foreach ( $liste as $i => $d ) {
			$deps[ $d['code'] ] = $enregistrer( 'dep:' . $d['code'], array( 'post_parent' => $etabs[ $sigle ], 'post_title' => $d['titre'], 'menu_order' => $i ), array( 'sigle' => $d['sigle'], 'groupe' => $d['groupe'] ? '1' : '' ) );
		}
	}
	$n = 0;
	foreach ( $offre['filieres'] as $i => $f ) {
		if ( empty( $deps[ $f['departement'] ] ) ) {
			$journal[] = 'département introuvable pour ' . $f['code'];
			continue;
		}
		$enregistrer( 'fil:' . $f['code'], array( 'post_parent' => $deps[ $f['departement'] ], 'post_title' => $f['titre'], 'menu_order' => $i ), array( 'diplome' => $f['diplome'], 'niveaux' => $f['niveaux'] ) );
		++$n;
	}
	$journal[] = count( $deps ) . " départements ou groupes, $n filières";
	return $journal;
}
