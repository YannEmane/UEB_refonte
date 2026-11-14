<?php
/**
 * Charge les unités d'enseignement (syllabus-estlc.php, syllabus-isabee.php) sous
 * leur filière, retrouvée par sa clé (méta « cle », « fil:ESTLC-MES »). À relancer
 * sans risque : chaque UE est retrouvée par sa clé « ue:<filière>:<code> » et mise
 * à jour ; le PDF de syllabus déjà joint à une UE est gardé. Les UE chargées par une
 * version précédente et disparues des données sont supprimées ; les UE saisies à
 * la main (sans clé) ne sont jamais touchées.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

function sueb_appliquer_syllabus( array $syllabus ) {
	$journal = array();
	wp_defer_term_counting( true );
	foreach ( $syllabus as $cle_filiere => $ues ) {
		$f = get_posts( array( 'post_type' => 'ueb_structure', 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => '_sueb_cle', 'meta_value' => $cle_filiere ) );
		if ( ! $f ) {
			$journal[] = "filière introuvable : $cle_filiere";
			continue;
		}
		$filiere = $f[0]->ID;
		$prefixe = 'ue:' . substr( $cle_filiere, 4 ) . ':';

		/* Les UE déjà chargées sous cette filière, par clé. */
		$deja = array();
		foreach ( get_children( array( 'post_parent' => $filiere, 'post_type' => 'ueb_structure', 'post_status' => 'any' ) ) as $u ) {
			$cle = (string) get_post_meta( $u->ID, '_sueb_cle', true );
			if ( 0 === strpos( $cle, $prefixe ) ) {
				$deja[ $cle ] = $u->ID;
			}
		}

		$gardees = array();
		foreach ( $ues as $i => $u ) {
			$cle    = $prefixe . $u['code'];
			$champs = array(
				'post_type'   => 'ueb_structure',
				'post_status' => 'publish',
				'post_parent' => $filiere,
				'post_title'  => $u['titre'],
				'menu_order'  => $i,
			);
			if ( isset( $deja[ $cle ] ) ) {
				$champs['ID'] = $deja[ $cle ];
				$id           = wp_update_post( $champs );
			} else {
				$id = wp_insert_post( $champs );
			}
			$metas = array(
				'cle'      => $cle,
				'code'     => $u['code'],
				'semestre' => $u['semestre'],
				'credits'  => $u['credits'] ? (string) $u['credits'] : '',
				'volume'   => $u['volume'],
				'elements' => implode( "\n", $u['elements'] ?? array() ),
			);
			foreach ( $metas as $k => $v ) {
				if ( '' === $v ) {
					delete_post_meta( $id, '_sueb_' . $k );
				} else {
					update_post_meta( $id, '_sueb_' . $k, $v );
				}
			}
			$gardees[ $cle ] = true;
		}

		$retirees = 0;
		foreach ( array_diff_key( $deja, $gardees ) as $id ) {
			wp_delete_post( $id, true );
			++$retirees;
		}
		$journal[] = sprintf( '%s : %d UE%s', $cle_filiere, count( $ues ), $retirees ? ", $retirees retirées" : '' );
	}
	wp_defer_term_counting( false );
	return $journal;
}
