<?php
/**
 * La page d'un établissement (faculté, école, institut) : tout ce qui le
 * concerne, réuni en une fois pour templates/etablissement.php.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/** Adresse de la page de chaque établissement, par sigle (pour les liens de l'accueil, du menu et du pied de page). */
function sueb_liens_etablissements() {
	static $liens = null;
	if ( null === $liens ) {
		$liens = array();
		foreach ( get_posts( array( 'post_type' => 'ueb_structure', 'post_parent' => 0, 'numberposts' => -1 ) ) as $e ) {
			$sigle = strtoupper( (string) sueb_meta( $e->ID, 'sigle' ) );
			if ( $sigle ) {
				$liens[ $sigle ] = get_permalink( $e );
			}
		}
	}
	return $liens;
}

/** Lien vers la page d'un établissement, ou à défaut vers sa carte sur l'accueil. */
function sueb_lien_etablissement( $sigle ) {
	$liens = sueb_liens_etablissements();
	return $liens[ strtoupper( $sigle ) ] ?? home_url( '/#etab-' . strtolower( $sigle ) );
}

/** Niveau d'une UE d'après son semestre et le diplôme de la filière : S1-S2 donnent L1 (ou M1 pour un master), etc. */
function sueb_niveau_ue( $semestre, $diplome ) {
	if ( ! preg_match( '/(\d+)/', (string) $semestre, $m ) ) {
		return 'Autres';
	}
	$annee = (int) ceil( (int) $m[1] / 2 );
	if ( preg_match( '/^master/i', (string) $diplome ) ) {
		return 'M' . $annee;
	}
	if ( preg_match( '/^licence/i', (string) $diplome ) ) {
		return 'L' . $annee;
	}
	return 'Année ' . $annee;
}

/**
 * Tout un établissement : identité, présentation, direction, départements >
 * filières > niveaux > UE, équipe, établissements privés sous sa tutelle,
 * photos de campus et chiffres.
 */
function sueb_page_etablissement( $id ) {
	$e      = get_post( $id );
	$sigle  = strtoupper( (string) sueb_meta( $id, 'sigle' ) );
	$fixe   = sueb_etablissements()[ $sigle ] ?? array();
	$enfant = function ( $parent ) {
		return get_children( array( 'post_parent' => $parent, 'post_type' => 'ueb_structure', 'post_status' => 'publish', 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
	};

	/* Départements > filières > UE, les UE rangées par niveau. */
	$departements = array();
	$nb_filieres  = 0;
	$nb_ue        = 0;
	foreach ( $enfant( $id ) as $d ) {
		$filieres = array();
		foreach ( $enfant( $d->ID ) as $f ) {
			$diplome = (string) sueb_meta( $f->ID, 'diplome' );
			$niveaux = array();
			foreach ( $enfant( $f->ID ) as $u ) {
				$syllabus = (int) sueb_meta( $u->ID, 'syllabus' );
				$niveau   = sueb_niveau_ue( sueb_meta( $u->ID, 'semestre' ), $diplome );
				$niveaux[ $niveau ][] = array(
					'titre'    => $u->post_title,
					'code'     => (string) sueb_meta( $u->ID, 'code' ),
					'credits'  => (string) sueb_meta( $u->ID, 'credits' ),
					'semestre' => (string) sueb_meta( $u->ID, 'semestre' ),
					'volume'   => (string) sueb_meta( $u->ID, 'volume' ),
					'syllabus' => $syllabus ? (string) wp_get_attachment_url( $syllabus ) : '',
					'url'      => get_permalink( $u ),
				);
				++$nb_ue;
			}
			ksort( $niveaux, SORT_NATURAL );
			/* Les niveaux ouverts (champ de l'admin) passent d'abord, même sans unité d'enseignement publiée. */
			$offerts = array_values( array_filter( array_map( 'trim', explode( ',', (string) sueb_meta( $f->ID, 'niveaux' ) ) ) ) );
			$niveaux = array_merge( array_fill_keys( $offerts, array() ), $niveaux );
			foreach ( $niveaux as $n => $ues ) {
				usort( $niveaux[ $n ], function ( $a, $b ) {
					return array( $a['semestre'], $a['code'] ) <=> array( $b['semestre'], $b['code'] );
				} );
			}
			$filieres[] = array(
				'titre'       => $f->post_title,
				'diplome'     => $diplome,
				'responsable' => (string) sueb_meta( $f->ID, 'responsable' ),
				'texte'       => wp_strip_all_tags( $f->post_excerpt ?: $f->post_content ),
				'url'         => get_permalink( $f ),
				'niveaux'     => $niveaux,
				'credits'     => array_sum( array_map( 'intval', array_column( array_merge( array(), ...array_values( $niveaux ?: array( array() ) ) ), 'credits' ) ) ),
			);
			++$nb_filieres;
		}
		$departements[] = array(
			'groupe'      => (bool) sueb_meta( $d->ID, 'groupe' ),
			'titre'       => $d->post_title,
			'sigle'       => (string) sueb_meta( $d->ID, 'sigle' ),
			'responsable' => (string) sueb_meta( $d->ID, 'responsable' ),
			'texte'       => wp_strip_all_tags( $d->post_excerpt ?: $d->post_content ),
			'url'         => get_permalink( $d ),
			'filieres'    => $filieres,
		);
	}

	/* L'équipe : le personnel rattaché à l'établissement ; le doyen ou directeur à part. */
	$equipe    = array();
	$direction = null;
	foreach ( get_posts( array( 'post_type' => 'ueb_personnel', 'numberposts' => -1, 'meta_key' => '_sueb_etablissement', 'meta_value' => $id, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) as $p ) {
		$m = sueb_membre_personnel( $p );
		if ( ! $direction && has_term( 'doyens-directeurs', 'ueb_corps', $p ) ) {
			$direction = $m;
		}
		$equipe[] = $m;
	}

	/* Les établissements privés sous sa tutelle, avec les seules filières qu'il encadre. */
	$tutelle = array();
	foreach ( sueb_reseau_partenaires()['partenaires'] as $p ) {
		$sigles = array_map( 'strtoupper', array_map( 'trim', explode( ',', (string) ( $p['tutelle'] ?? '' ) ) ) );
		if ( ! in_array( $sigle, $sigles, true ) ) {
			continue;
		}
		$p['filieres'] = array();
		foreach ( $p['id'] ? sueb_filieres_partenaire( $p['id'] ) : array() as $f ) {
			if ( ! $f['tutelles'] || in_array( $sigle, array_map( 'strtoupper', $f['tutelles'] ), true ) ) {
				$p['filieres'][] = $f['nom'];
			}
		}
		$tutelle[] = $p;
	}

	/* Photos : l'image mise en avant, celles de l'établissement, puis le campus de sa ville. */
	$ville    = (string) sueb_meta( $id, 'ville' ) ?: ( $fixe['ville'] ?? '' );
	$campus   = array( 'Ebolowa' => 'campus-ebolowa', 'Sangmélima' => 'campus-sangmelima', 'Ambam' => 'campus-ambam', 'Kribi' => 'visite-port' );
	$photos   = array();
	if ( has_post_thumbnail( $id ) ) {
		$photos[] = array( 'src' => (string) get_the_post_thumbnail_url( $id, 'large' ), 'legende' => '' );
	}
	foreach ( $fixe['photos'] ?? array() as $nom ) {
		$photos[] = array( 'src' => sueb_photo( $nom ), 'legende' => '' );
	}
	if ( isset( $campus[ $ville ] ) ) {
		$photos[] = array( 'src' => sueb_photo( $campus[ $ville ] ), 'legende' => 'Campus de ' . $ville );
	}
	$photos = array_slice( array_values( array_intersect_key( $photos, array_unique( array_column( $photos, 'src' ) ) ) ), 0, 4 );

	$nom      = $e->post_title;
	$feminin  = ! preg_match( '/^institut/i', $nom );
	$presente = trim( (string) $e->post_content );

	return array(
		'id'           => $id,
		'sigle'        => $sigle,
		'nom'          => $nom,
		'ville'        => $ville,
		'feminin'      => $feminin,
		'couleur'      => (string) sueb_meta( $id, 'couleur' ) ?: ( $fixe['couleur'] ?? '#163d2b' ),
		'logo'         => sueb_image_url( $id, 'medium', 'logo' ) ?: sueb_logo( $sigle ),
		'etudiant'     => sueb_photo_etudiant( $sigle ),
		'presentation' => $presente,
		'departements' => $departements,
		'direction'    => $direction,
		'equipe'       => $equipe,
		'tutelle'      => $tutelle,
		'photos'       => $photos,
		'chiffres'     => array(
			'départements'                     => count( array_filter( $departements, function ( $d ) {
				return ! $d['groupe'];
			} ) ),
			'filières'                         => $nb_filieres,
			'unités d’enseignement'            => $nb_ue,
			'établissements privés encadrés'   => count( $tutelle ),
		),
	);
}

/** Présentation de repli, tirée des données, quand l'établissement n'a pas encore de texte dans l'admin. */
function sueb_presentation_etablissement( array $p ) {
	$il      = $p['feminin'] ? 'Elle' : 'Il';
	$le      = preg_match( '/^[aeiouyéèêàâîôûh]/iu', $p['nom'] ) ? 'L’' : ( $p['feminin'] ? 'La ' : 'Le ' );
	$phrases = array( sprintf( '%s%s (%s) est l’un des neuf établissements de l’Université d’Ebolowa%s.', $le, $p['nom'], $p['sigle'], $p['ville'] ? ', installé' . ( $p['feminin'] ? 'e' : '' ) . ' à ' . $p['ville'] : '' ) );
	if ( $p['direction'] && ! $p['direction']['a_nommer'] ) {
		$phrases[] = sprintf( '%s est dirigé%s par le %s.', $il, $p['feminin'] ? 'e' : '', $p['direction']['nom'] );
	}
	$nd = $p['chiffres']['départements'];
	$nf = $p['chiffres']['filières'];
	if ( $nd ) {
		$phrases[] = sprintf( '%s compte %d département%s et %d filière%s.', $il, $nd, $nd > 1 ? 's' : '', $nf, $nf > 1 ? 's' : '' );
	} elseif ( $nf ) {
		$phrases[] = sprintf( '%s propose %d filière%s.', $il, $nf, $nf > 1 ? 's' : '' );
	}
	if ( $p['tutelle'] ) {
		$n         = count( $p['tutelle'] );
		$phrases[] = sprintf( '%s assure la tutelle académique %s : %s.', $il, 1 === $n ? 'd’un établissement privé' : 'de ' . $n . ' établissements privés', implode( ', ', array_column( $p['tutelle'], 'sigle' ) ) );
	}
	return $phrases;
}
