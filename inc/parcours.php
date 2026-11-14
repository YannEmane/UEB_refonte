<?php
/**
 * Les parcours de formation : classique, professionnel, BTS et ingénierie,
 * médecine. La page Académie (archive-ueb_structure.php) les présente en trois
 * temps : les parcours, puis les établissements qui proposent le parcours
 * choisi (/academie/parcours/classique/), puis les filières de ce parcours
 * dans l'établissement choisi (/academie/parcours/classique/fs/).
 *
 * Le parcours d'une filière se déduit de ses niveaux et de son diplôme ; le
 * champ « Parcours » de la filière, dans la gestion, permet de le fixer.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/** Les parcours, dans l'ordre de la page Académie : identifiant => nom, nom court, « en … », phrase, diplômes, icône, couleur. */
function sueb_parcours() {
	return array(
		'classique'      => array(
			'nom'      => 'Parcours classique',
			'dans'     => 'en parcours classique',
			'court'    => 'Classique',
			'texte'    => 'Les formations générales du système Licence, Master, Doctorat : la licence en trois ans, puis le master, ouverts à la recherche comme à l’emploi.',
			'diplomes' => array( 'Licence', 'Master', 'DIPET' ),
			'icone'    => 'toque',
			'couleur'  => '#1d5a38',
		),
		'pro'            => array(
			'nom'      => 'Parcours professionnel',
			'dans'     => 'en parcours professionnel',
			'court'    => 'Professionnel',
			'texte'    => 'Des formations tournées vers un métier, construites avec les entreprises : licences et masters professionnels, stages et projets de terrain.',
			'diplomes' => array( 'Licence professionnelle', 'Master professionnel' ),
			'icone'    => 'mallette',
			'couleur'  => '#1f6f94',
		),
		'bts-ingenierie' => array(
			'nom'      => 'BTS et ingénierie',
			'dans'     => 'en BTS et ingénierie',
			'court'    => 'BTS et ingénierie',
			'texte'    => 'Les formations techniques : le brevet de technicien supérieur en deux ans et les cycles d’ingénieur des grandes écoles et de l’institut.',
			'diplomes' => array( 'BTS', 'Diplôme d’ingénieur' ),
			'icone'    => 'engrenage',
			'couleur'  => '#9a6b00',
		),
		'medecine'       => array(
			'nom'      => 'Médecine',
			'dans'     => 'en médecine',
			'court'    => 'Médecine',
			'texte'    => 'Les études médicales et pharmaceutiques de la Faculté de Médecine et des Sciences Pharmaceutiques, jusqu’au doctorat d’exercice.',
			'diplomes' => array( 'Doctorat en médecine', 'Doctorat en pharmacie' ),
			'icone'    => 'sante',
			'couleur'  => '#a3323c',
		),
	);
}

/** Les établissements qui forment des ingénieurs : leurs cycles numérotés ou en licence et master relèvent de l'ingénierie. */
function sueb_ecoles_ingenieurs() {
	return array( 'ESTLC', 'ENSTMO', 'ISABEE' );
}

/** Les niveaux ouverts d'une filière (champ « Niveaux ouverts »), en liste. */
function sueb_liste_niveaux( $niveaux ) {
	return array_values( array_filter( array_map( 'trim', explode( ',', (string) $niveaux ) ) ) );
}

/**
 * Les parcours d'une filière, chacun avec ses niveaux : array( parcours => niveaux ). Une filière peut
 * en avoir plusieurs (BTS puis licence professionnelle). Le champ « Parcours » l'emporte ; sinon ils
 * se déduisent des niveaux ouverts, puis du diplôme. Une formation doctorale (Ph.D) n'entre dans aucun.
 */
function sueb_parcours_filiere( $choisis, $diplome, $niveaux, $sigle_etab ) {
	$connus  = sueb_parcours();
	$niveaux = sueb_liste_niveaux( $niveaux );
	$choisis = array_intersect( array_map( 'sanitize_title', sueb_liste_niveaux( $choisis ) ), array_keys( $connus ) );
	if ( $choisis ) {
		return array_fill_keys( array_unique( $choisis ), $niveaux );
	}
	$diplome = (string) $diplome;
	if ( preg_match( '/doctorat en (m[ée]decine|pharmacie)/iu', $diplome ) ) {
		return array( 'medecine' => $niveaux );
	}
	if ( preg_match( '/doctorat|ph\.?\s?d/iu', $diplome ) ) {
		return array();
	}
	$base    = in_array( strtoupper( (string) $sigle_etab ), sueb_ecoles_ingenieurs(), true ) ? 'bts-ingenierie' : 'classique';
	$trouves = array();
	foreach ( $niveaux as $n ) {
		if ( preg_match( '/bts|^niveau\s*\d/iu', $n ) ) {
			$trouves['bts-ingenierie'][] = $n;
		} elseif ( preg_match( '/\bpro\b|^[LM]P\s?\d/iu', $n ) ) {
			$trouves['pro'][] = $n;
		} elseif ( preg_match( '/^[LM]\s?\d$|cycle/iu', $n ) ) {
			$trouves[ $base ][] = $n;
		}
	}
	if ( ! $trouves ) {
		if ( preg_match( '/ing[ée]nieur|\bbts\b|brevet de technicien/iu', $diplome ) ) {
			$trouves['bts-ingenierie'] = $niveaux;
		} elseif ( preg_match( '/professionnel/iu', $diplome ) ) {
			$trouves['pro'] = $niveaux;
		} else {
			$trouves[ $base ] = $niveaux;
		}
	}
	return array_intersect_key( $trouves, $connus );
}

/**
 * L'offre rangée par parcours : parcours => sigle d'établissement => array( etab, filieres ).
 * Chaque filière : id, titre, code, url, diplôme, niveaux du parcours, département (vide pour un simple groupe).
 */
function sueb_offre_par_parcours() {
	static $offre = null;
	if ( null !== $offre ) {
		return $offre;
	}
	$offre = array_fill_keys( array_keys( sueb_parcours() ), array() );
	foreach ( sueb_arbre_formation() as $e ) {
		$sigle = strtoupper( $e['sigle'] );
		foreach ( $e['enfants'] as $d ) {
			$groupe = (bool) sueb_meta( $d['id'], 'groupe' );
			foreach ( $d['enfants'] as $f ) {
				$niveaux = (string) sueb_meta( $f['id'], 'niveaux' );
				list( $titre, $code ) = sueb_titre_et_code( $f['titre'] );
				foreach ( sueb_parcours_filiere( sueb_meta( $f['id'], 'parcours' ), $f['diplome'], $niveaux, $sigle ) as $p => $du_parcours ) {
					if ( ! isset( $offre[ $p ][ $sigle ] ) ) {
						$offre[ $p ][ $sigle ] = array( 'etab' => array_diff_key( $e, array( 'enfants' => 1 ) ), 'filieres' => array() );
					}
					$offre[ $p ][ $sigle ]['filieres'][] = array(
						'id'          => $f['id'],
						'titre'       => $titre,
						'code'        => $code,
						'url'         => $f['url'],
						'diplome'     => $f['diplome'],
						'niveaux'     => $du_parcours, // les seuls niveaux de ce parcours
						'departement' => $groupe ? '' : $d['titre'],
					);
				}
			}
		}
	}
	return $offre;
}

/** Adresse d'un parcours sur la page Académie, ou de ses filières dans un établissement (sigle). */
function sueb_url_parcours( $parcours, $sigle = '' ) {
	return user_trailingslashit( trailingslashit( sueb_url_academie() ) . 'parcours/' . $parcours . ( $sigle ? '/' . strtolower( $sigle ) : '' ) );
}

/** Le parcours et l'établissement demandés par l'adresse : array( parcours, sigle ), vides sur la page Académie elle-même. */
function sueb_parcours_demande() {
	return array( (string) get_query_var( 'sueb_parcours' ), strtoupper( (string) get_query_var( 'sueb_etab' ) ) );
}

/* Les adresses /academie/parcours/<parcours>/ et /academie/parcours/<parcours>/<sigle>/ (règles vidées dans inc/etablissement.php). */
add_action( 'init', function () {
	add_rewrite_rule( '^academie/parcours/([^/]+)/?$', 'index.php?post_type=ueb_structure&sueb_parcours=$matches[1]', 'top' );
	add_rewrite_rule( '^academie/parcours/([^/]+)/([^/]+)/?$', 'index.php?post_type=ueb_structure&sueb_parcours=$matches[1]&sueb_etab=$matches[2]', 'top' );
} );
add_filter( 'query_vars', function ( $vars ) {
	return array_merge( $vars, array( 'sueb_parcours', 'sueb_etab' ) );
} );

/* Un parcours inconnu, ou un établissement qui ne propose pas ce parcours : page introuvable. */
add_action( 'wp', function () {
	list( $parcours, $sigle ) = sueb_parcours_demande();
	if ( '' === $parcours || ! is_post_type_archive( 'ueb_structure' ) ) {
		return;
	}
	$offre = sueb_offre_par_parcours();
	if ( ! isset( $offre[ $parcours ] ) || ( '' !== $sigle && ! isset( $offre[ $parcours ][ $sigle ] ) ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
} );

/* Le titre de l'onglet : « Parcours classique », « FS en parcours classique ». */
add_filter( 'document_title_parts', function ( $parties ) {
	list( $parcours, $sigle ) = sueb_parcours_demande();
	$p = sueb_parcours()[ $parcours ] ?? null;
	if ( $p && is_post_type_archive( 'ueb_structure' ) ) {
		$parties['title'] = $sigle ? $sigle . ' ' . $p['dans'] : $p['nom'];
	}
	return $parties;
}, 20 );
