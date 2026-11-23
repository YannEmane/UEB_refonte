<?php
/**
 * Les parcours de formation : classique, professionnel, BTS et ingénierie,
 * médecine. La page Académie (archive-ueb_structure.php) les présente pas à
 * pas : les parcours, puis les établissements qui proposent le parcours choisi
 * (/academie/parcours/classique/), puis ses départements dans l'établissement
 * (/academie/parcours/classique/fs/), puis les filières du département
 * (/academie/parcours/classique/fs/<departement>/) ; la page d'une filière
 * donne ses unités d'enseignement.
 *
 * Le parcours d'une filière se déduit de ses niveaux et de son diplôme ; le
 * champ « Parcours » de la filière, dans la gestion, permet de le fixer.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/** Les parcours, dans l'ordre de la page Académie : identifiant => nom, nom court, « en … », phrase, diplômes,
 * niveaux proposés dans la gestion (cases « Niveaux ouverts » de la filière), icône, couleur. */
function sueb_parcours() {
	return array(
		'classique'      => array(
			'nom'      => 'Parcours classique',
			'dans'     => 'en parcours classique',
			'court'    => 'Classique',
			'texte'    => 'Les formations générales du système Licence, Master, Doctorat : la licence en trois ans, puis le master, ouverts à la recherche comme à l’emploi.',
			'diplomes' => array( 'Licence', 'Master', 'DIPET' ),
			'niveaux'  => array( 'L1', 'L2', 'L3', 'M1', 'M2' ),
			'icone'    => 'toque',
			'couleur'  => '#1d5a38',
		),
		'pro'            => array(
			'nom'      => 'Parcours professionnel',
			'dans'     => 'en parcours professionnel',
			'court'    => 'Professionnel',
			'texte'    => 'Des formations tournées vers un métier, construites avec les entreprises : licences et masters professionnels, stages et projets de terrain.',
			'diplomes' => array( 'Licence professionnelle', 'Master professionnel' ),
			'niveaux'  => array( 'L1 pro', 'L2 pro', 'L3 pro', 'M1 pro', 'M2 pro' ),
			'icone'    => 'mallette',
			'couleur'  => '#1f6f94',
		),
		'bts-ingenierie' => array(
			'nom'      => 'BTS et ingénierie',
			'dans'     => 'en BTS et ingénierie',
			'court'    => 'BTS et ingénierie',
			'texte'    => 'Les formations techniques : le brevet de technicien supérieur en deux ans et les cycles d’ingénieur des grandes écoles et de l’institut.',
			'diplomes' => array( 'BTS', 'Diplôme d’ingénieur' ),
			'niveaux'  => array( 'BTS 1', 'BTS 2', 'Niveau 1', 'Niveau 2', 'Niveau 3', 'Niveau 4', 'Niveau 5' ),
			'icone'    => 'engrenage',
			'couleur'  => '#9a6b00',
		),
		'medecine'       => array(
			'nom'      => 'Médecine',
			'dans'     => 'en médecine',
			'court'    => 'Médecine',
			'texte'    => 'Les études médicales et pharmaceutiques de la Faculté de Médecine et des Sciences Pharmaceutiques, jusqu’au doctorat d’exercice.',
			'diplomes' => array( 'Doctorat en médecine', 'Doctorat en pharmacie' ),
			'niveaux'  => array( 'A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7' ),
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
 * en avoir plusieurs (BTS puis licence professionnelle). Un niveau qui dit son parcours le fixe (BTS 1,
 * Niveau 3, L3 pro, A2) ; les autres (L1, M2, 1er cycle…) suivent le parcours choisi dans la gestion
 * (champ « Parcours »), à défaut le parcours habituel de l'établissement. Sans niveau, le parcours
 * choisi, puis le diplôme. Une formation doctorale (Ph.D) n'entre dans aucun parcours.
 */
function sueb_parcours_filiere( $choisi, $diplome, $niveaux, $sigle_etab ) {
	$connus  = sueb_parcours();
	$niveaux = sueb_liste_niveaux( $niveaux );
	$choisi  = sanitize_title( (string) ( sueb_liste_niveaux( $choisi )[0] ?? '' ) );
	$choisi  = isset( $connus[ $choisi ] ) ? $choisi : '';
	$diplome = (string) $diplome;
	if ( 'medecine' === $choisi || preg_match( '/doctorat en (m[ée]decine|pharmacie)/iu', $diplome ) ) {
		return array( 'medecine' => $niveaux );
	}
	if ( ! $choisi && preg_match( '/doctorat|ph\.?\s?d/iu', $diplome ) ) {
		return array();
	}
	$base    = $choisi ?: ( in_array( strtoupper( (string) $sigle_etab ), sueb_ecoles_ingenieurs(), true ) ? 'bts-ingenierie' : 'classique' );
	$trouves = array();
	foreach ( $niveaux as $n ) {
		if ( preg_match( '/bts|^niveau\s*\d/iu', $n ) ) {
			$trouves['bts-ingenierie'][] = $n;
		} elseif ( preg_match( '/\bpro\b|^[LM]P\s?\d/iu', $n ) ) {
			$trouves['pro'][] = $n;
		} elseif ( preg_match( '/^A\s?\d$/iu', $n ) ) {
			$trouves['medecine'][] = $n;
		} else {
			$trouves[ $base ][] = $n;
		}
	}
	if ( ! $trouves ) {
		if ( $choisi ) {
			$trouves[ $choisi ] = array();
		} elseif ( preg_match( '/ing[ée]nieur|\bbts\b|brevet de technicien/iu', $diplome ) ) {
			$trouves['bts-ingenierie'] = array();
		} elseif ( preg_match( '/professionnel/iu', $diplome ) ) {
			$trouves['pro'] = array();
		} else {
			$trouves[ $base ] = array();
		}
	}
	return array_intersect_key( $trouves, $connus );
}

/**
 * L'offre rangée par parcours : parcours => sigle d'établissement => array( etab, filieres, departements ).
 * Les départements (ou simples groupes de filières) : adresse => array( id, titre, sigle, groupe, filieres ).
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
			$adresse = (string) get_post_field( 'post_name', $d['id'] );
			foreach ( $d['enfants'] as $f ) {
				$niveaux = (string) sueb_meta( $f['id'], 'niveaux' );
				list( $titre, $code ) = sueb_titre_et_code( $f['titre'] );
				foreach ( sueb_parcours_filiere( sueb_meta( $f['id'], 'parcours' ), $f['diplome'], $niveaux, $sigle ) as $p => $du_parcours ) {
					if ( ! isset( $offre[ $p ][ $sigle ] ) ) {
						$offre[ $p ][ $sigle ] = array( 'etab' => array_diff_key( $e, array( 'enfants' => 1 ) ), 'filieres' => array(), 'departements' => array() );
					}
					if ( ! isset( $offre[ $p ][ $sigle ]['departements'][ $adresse ] ) ) {
						$offre[ $p ][ $sigle ]['departements'][ $adresse ] = array( 'id' => $d['id'], 'titre' => $d['titre'], 'sigle' => $d['sigle'], 'groupe' => $groupe, 'filieres' => array() );
					}
					$fiche = array(
						'id'          => $f['id'],
						'titre'       => $titre,
						'code'        => $code,
						'url'         => $f['url'],
						'diplome'     => $f['diplome'],
						'niveaux'     => $du_parcours, // les seuls niveaux de ce parcours
						'departement' => $groupe ? '' : $d['titre'],
					);
					$offre[ $p ][ $sigle ]['filieres'][]                             = $fiche;
					$offre[ $p ][ $sigle ]['departements'][ $adresse ]['filieres'][] = $fiche;
				}
			}
		}
	}
	return $offre;
}

/**
 * L'étape « département » a-t-elle lieu d'être pour ce choix (sueb_offre_par_parcours()[parcours][sigle]) ?
 * Non quand toutes les filières sont dans un seul groupe sans département (« Filières ») : on les montre aussitôt.
 */
function sueb_parcours_par_departement( array $choix ) {
	$deps = array_values( $choix['departements'] );
	return count( $deps ) > 1 || ( $deps && ! $deps[0]['groupe'] );
}

/** Adresse d'un parcours sur la page Académie, de ses départements dans un établissement (sigle), de ses filières dans un département. */
function sueb_url_parcours( $parcours, $sigle = '', $departement = '' ) {
	return user_trailingslashit( trailingslashit( sueb_url_academie() ) . 'parcours/' . $parcours . ( $sigle ? '/' . strtolower( $sigle ) . ( $departement ? '/' . $departement : '' ) : '' ) );
}

/**
 * Adresse de l'entrée par établissement (menu Académie de l'en-tête) : ses formations (/academie/fs/),
 * les départements d'une formation (/academie/fs/classique/), les filières d'un département
 * (/academie/fs/classique/<departement>/). Mêmes pages que l'entrée par parcours, dans l'autre ordre.
 */
function sueb_url_formation( $sigle, $parcours = '', $departement = '' ) {
	return user_trailingslashit( trailingslashit( sueb_url_academie() ) . strtolower( $sigle ) . ( $parcours ? '/' . $parcours . ( $departement ? '/' . $departement : '' ) : '' ) );
}

/** Le parcours, l'établissement et le département demandés par l'adresse : array( parcours, sigle, département ), vides sur la page Académie elle-même. */
function sueb_parcours_demande() {
	return array( (string) get_query_var( 'sueb_parcours' ), strtoupper( (string) get_query_var( 'sueb_etab' ) ), sanitize_title( (string) get_query_var( 'sueb_dep' ) ) );
}

/** La page a-t-elle été ouverte par l'établissement (/academie/fs/…) plutôt que par le parcours (/academie/parcours/…) ? */
function sueb_academie_par_etab() {
	return 'etab' === get_query_var( 'sueb_ordre' );
}

/** L'établissement d'un sigle, tel que l'arbre des formations le présente (titre, logo, couleur, ville…), ou null. */
function sueb_etab_par_sigle( $sigle ) {
	foreach ( sueb_arbre_formation() as $e ) {
		if ( strtoupper( $e['sigle'] ) === strtoupper( $sigle ) ) {
			return array_diff_key( $e, array( 'enfants' => 1 ) );
		}
	}
	return null;
}

/** « la FS », « l’ENSET » : le sigle précédé de son article (nom complet : les instituts sont au masculin). */
function sueb_le_sigle( $sigle, $nom = '' ) {
	if ( preg_match( '/^[AEIOUY]/i', $sigle ) ) {
		return 'l’' . $sigle;
	}
	return ( preg_match( '/^institut/i', $nom ) ? 'le ' : 'la ' ) . $sigle;
}

/** « de la FS », « de l’ENSET », « du … ». */
function sueb_de_sigle( $sigle, $nom = '' ) {
	$le = sueb_le_sigle( $sigle, $nom );
	return 0 === strpos( $le, 'le ' ) ? 'du ' . substr( $le, 3 ) : 'de ' . $le;
}

/*
 * Les adresses /academie/parcours/<parcours>/, …/<sigle>/ et …/<sigle>/<département>/, puis celles de
 * l'entrée par établissement, /academie/<sigle>/, …/<parcours>/ et …/<parcours>/<département>/ (règles
 * vidées dans inc/etablissement.php). Celles-ci écartent « parcours » et les adresses d'archive de WordPress.
 */
add_action( 'init', function () {
	add_rewrite_rule( '^academie/parcours/([^/]+)/?$', 'index.php?post_type=ueb_structure&sueb_parcours=$matches[1]', 'top' );
	add_rewrite_rule( '^academie/parcours/([^/]+)/([^/]+)/?$', 'index.php?post_type=ueb_structure&sueb_parcours=$matches[1]&sueb_etab=$matches[2]', 'top' );
	add_rewrite_rule( '^academie/parcours/([^/]+)/([^/]+)/([^/]+)/?$', 'index.php?post_type=ueb_structure&sueb_parcours=$matches[1]&sueb_etab=$matches[2]&sueb_dep=$matches[3]', 'top' );
	$sigle = '(?!(?:parcours|page|feed|rdf|rss|rss2|atom)(?:/|$))([^/]+)';
	add_rewrite_rule( '^academie/' . $sigle . '/?$', 'index.php?post_type=ueb_structure&sueb_ordre=etab&sueb_etab=$matches[1]', 'top' );
	add_rewrite_rule( '^academie/' . $sigle . '/([^/]+)/?$', 'index.php?post_type=ueb_structure&sueb_ordre=etab&sueb_etab=$matches[1]&sueb_parcours=$matches[2]', 'top' );
	add_rewrite_rule( '^academie/' . $sigle . '/([^/]+)/([^/]+)/?$', 'index.php?post_type=ueb_structure&sueb_ordre=etab&sueb_etab=$matches[1]&sueb_parcours=$matches[2]&sueb_dep=$matches[3]', 'top' );
} );
add_filter( 'query_vars', function ( $vars ) {
	return array_merge( $vars, array( 'sueb_parcours', 'sueb_etab', 'sueb_dep', 'sueb_ordre' ) );
} );

/* Un parcours inconnu, un établissement inconnu ou qui ne propose pas le parcours, un département sans filière de ce parcours : page introuvable. */
add_action( 'wp', function () {
	list( $parcours, $sigle, $dep ) = sueb_parcours_demande();
	$par_etab = sueb_academie_par_etab();
	if ( ( '' === $parcours && ! $par_etab ) || ! is_post_type_archive( 'ueb_structure' ) ) {
		return;
	}
	$offre = sueb_offre_par_parcours();
	if ( $par_etab && '' === $parcours ) {
		$introuvable = '' === $sigle || ! sueb_etab_par_sigle( $sigle );
	} else {
		$introuvable = ! isset( $offre[ $parcours ] ) || ( '' !== $sigle && ! isset( $offre[ $parcours ][ $sigle ] ) ) || ( '' !== $dep && ! isset( $offre[ $parcours ][ $sigle ]['departements'][ $dep ] ) );
	}
	if ( $introuvable ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
} );

/* Le titre de l'onglet : « Parcours classique », « FS en parcours classique », « Département de … (FS) en parcours classique » ;
   par l'établissement, d'abord « Formations de la FS ». */
add_filter( 'document_title_parts', function ( $parties ) {
	list( $parcours, $sigle, $dep ) = sueb_parcours_demande();
	if ( ! is_post_type_archive( 'ueb_structure' ) ) {
		return $parties;
	}
	$p = sueb_parcours()[ $parcours ] ?? null;
	if ( $p ) {
		$d                = $dep ? ( sueb_offre_par_parcours()[ $parcours ][ $sigle ]['departements'][ $dep ] ?? null ) : null;
		$parties['title'] = $d ? $d['titre'] . ' (' . $sigle . ') ' . $p['dans'] : ( $sigle ? $sigle . ' ' . $p['dans'] : $p['nom'] );
	} elseif ( sueb_academie_par_etab() && ( $e = sueb_etab_par_sigle( $sigle ) ) ) {
		$parties['title'] = 'Formations ' . sueb_de_sigle( $sigle, $e['titre'] );
	}
	return $parties;
}, 20 );
