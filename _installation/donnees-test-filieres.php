<?php
/**
 * Données de test des pages de filière : un texte « À propos de la filière » et un programme des cours
 * (UE par niveau et par semestre), pour voir les pages remplies avant la saisie des vraies données.
 *
 * Tout est marqué : le texte commence par « Texte d'exemple » (méta _sueb_demo_apropos = 1) et chaque UE
 * porte la méta _sueb_demo = ue. Une filière qui a déjà un texte ou des UE n'est pas touchée.
 *
 * En ligne de commande, depuis le dossier du thème :
 *   /opt/lampp/bin/php _installation/donnees-test-filieres.php              charge les données de test
 *   /opt/lampp/bin/php _installation/donnees-test-filieres.php --supprimer  les retire toutes
 *
 * À ne jamais lancer sur le site en ligne.
 *
 * @package Site_UEB
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}
require dirname( __DIR__, 4 ) . '/wp-load.php';

/* ---------- Suppression ---------- */
if ( in_array( '--supprimer', $argv, true ) ) {
	$ues = get_posts( array( 'post_type' => 'ueb_structure', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_sueb_demo', 'meta_value' => 'ue' ) );
	foreach ( $ues as $id ) {
		wp_delete_post( $id, true );
	}
	$textes = get_posts( array( 'post_type' => 'ueb_structure', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_sueb_demo_apropos', 'meta_value' => '1' ) );
	foreach ( $textes as $id ) {
		wp_update_post( array( 'ID' => $id, 'post_content' => '' ) );
		delete_post_meta( $id, '_sueb_demo_apropos' );
	}
	printf( "%d UE et %d textes d'exemple supprimés\n", count( $ues ), count( $textes ) );
	exit;
}

/* ---------- Chargement ---------- */
wp_defer_term_counting( true );
wp_suspend_cache_invalidation( true );

global $wpdb;
$filieres = $wpdb->get_results( "SELECT post_id AS id, meta_value AS cle FROM {$wpdb->postmeta} WHERE meta_key = '_sueb_cle' AND meta_value LIKE 'fil:%'" ); // phpcs:ignore WordPress.DB

/* Les UE communes, au début des études puis en fin de parcours (une liste qui tourne d'un semestre à l'autre). */
$communes = array(
	'debut' => array( 'Mathématiques générales', 'Informatique et bureautique', 'Méthodologie du travail universitaire', 'Expression écrite et orale', 'Anglais', 'Économie générale', 'Statistique descriptive', 'Culture générale' ),
	'fin'   => array( 'Méthodologie de la recherche', 'Anglais professionnel', 'Gestion de projet', 'Statistique appliquée', 'Droit et éthique professionnelle', 'Entrepreneuriat', 'Communication professionnelle', 'Analyse de données' ),
);
/* Un niveau avancé (master, cycle d'ingénieur, 2nd cycle, fin des études médicales) prend les UE de fin de parcours. */
$avance = function ( $niveau ) {
	return (bool) preg_match( '/^M\s?\d|^MP|2nd cycle|^niveau\s*[3-5]|^A\s?[4-7]/iu', $niveau );
};
/* Le rang d'un niveau dans son cycle : L2 donne 2, BTS 1 donne 1, 2nd cycle donne 2. */
$rang = function ( $niveau, $par_defaut ) {
	if ( preg_match( '/(\d+)/', $niveau, $m ) ) {
		return (int) $m[1];
	}
	return $par_defaut;
};

$nb_ue    = 0;
$nb_texte = 0;
foreach ( $filieres as $fil ) {
	$id      = (int) $fil->id;
	$niveaux = sueb_liste_niveaux( get_post_meta( $id, '_sueb_niveaux', true ) );
	$diplome = (string) get_post_meta( $id, '_sueb_diplome', true );
	/* L'intitulé sans « , master professionnel » puis sans son code : « Comptabilité, contrôle et audit ». */
	list( $titre ) = sueb_titre_et_code( preg_replace( '/,\s*(master|licence) professionnel(le)?$/iu', '', get_the_title( $id ) ) );
	$racine  = sueb_racine( $id );
	$sigle   = (string) get_post_meta( $racine, '_sueb_sigle', true );
	$etab    = get_the_title( $racine );
	$ville   = sueb_etablissements()[ strtoupper( $sigle ) ]['ville'] ?? '';

	/* Le texte « À propos », s'il n'y en a pas encore. */
	if ( '' === trim( (string) get_post_field( 'post_content', $id ) ) ) {
		$noms   = array_map( 'sueb_nom_niveau', $niveaux );
		$texte  = '<p><em>Texte d’exemple, à remplacer par la présentation officielle de la filière.</em></p>';
		$texte .= '<p>' . esc_html( sprintf( 'La filière %s est l’une des formations %s (%s)%s, à l’Université d’Ebolowa.', $titre, sueb_de_sigle( $sigle, $etab ), $etab, $ville ? ', à ' . $ville : '' ) );
		$texte .= $diplome ? ' ' . esc_html( 'Diplôme préparé : ' . $diplome . '.' ) : '';
		$texte .= $noms ? ' ' . esc_html( ( count( $noms ) > 1 ? 'Niveaux ouverts : ' : 'Niveau ouvert : ' ) . implode( ', ', $noms ) . '.' ) : '';
		$texte .= '</p>';
		$texte .= '<p>La formation associe des enseignements fondamentaux, des travaux dirigés et pratiques et une ouverture sur le monde professionnel, avec un stage en fin de parcours.</p>';
		$texte .= '<h3>Objectifs de la formation</h3><ul><li>Acquérir les connaissances fondamentales du domaine.</li><li>Maîtriser les méthodes et les outils propres à la discipline.</li><li>Travailler en équipe et communiquer à l’écrit comme à l’oral.</li><li>Se préparer à l’insertion professionnelle ou à la poursuite d’études.</li></ul>';
		$texte .= '<h3>Débouchés</h3><p>Les diplômés peuvent travailler dans les entreprises, les administrations et les organisations du secteur, ou poursuivre leurs études.</p>';
		$texte .= '<h3>Conditions d’admission</h3><p>L’admission se fait sur étude de dossier ou par concours, selon le niveau d’entrée.</p>';
		wp_update_post( array( 'ID' => $id, 'post_content' => $texte ) );
		update_post_meta( $id, '_sueb_demo_apropos', '1' );
		++$nb_texte;
	}

	/* Le programme des cours, s'il n'y a encore aucune UE. */
	if ( ! $niveaux || get_children( array( 'post_parent' => $id, 'post_type' => 'ueb_structure', 'numberposts' => 1, 'post_status' => 'any' ) ) ) {
		continue;
	}
	/* Le préfixe des codes : la clé sans l'établissement ni « PRO » / « LP » (fil:FSEG-CCA-PRO donne CCA). */
	$parties = array_diff( array_slice( explode( '-', substr( $fil->cle, 4 ) ), 1 ), array( 'PRO', 'LP' ) );
	$prefixe = strtoupper( substr( preg_replace( '/[^A-Z]/i', '', implode( '', $parties ) ), 0, 4 ) ) ?: 'UE';
	$k       = 0; // numéro des UE de la discipline, d'un bout à l'autre du parcours
	$tour    = 0; // position dans la liste des UE communes
	$ordre   = 0;
	foreach ( $niveaux as $i => $niveau ) {
		$r       = $rang( $niveau, $i + 1 );
		$liste   = $communes[ $avance( $niveau ) ? 'fin' : 'debut' ];
		$dernier = $i === count( $niveaux ) - 1;
		foreach ( array( 1, 2 ) as $s ) {
			$semestre = 'S' . ( 2 * $r - 2 + $s );
			$code     = $prefixe . ( $i + 1 ) . $s; // le rang du niveau dans la filière : deux niveaux ne partagent pas un code
			$ues      = array();
			if ( $dernier && 2 === $s ) {
				/* La fin du parcours : le stage et le mémoire (ou le projet de fin d'études). */
				$ues[] = array( preg_match( '/ing[ée]nieur|^niveau/iu', $diplome . ' ' . $niveau ) ? 'Projet de fin d’études' : 'Stage et mémoire', 30, '6 mois' );
			} else {
				$ues[] = array( $titre . ' ' . ( ++$k ), 6, '60 h : 30 CM, 20 TD, 10 TP' );
				$ues[] = array( $titre . ' ' . ( ++$k ), 6, '60 h : 30 CM, 15 TD, 15 TP' );
				$ues[] = array( $titre . ' ' . ( ++$k ), 6, '60 h : 25 CM, 25 TD, 10 TP' );
				$ues[] = array( $liste[ $tour++ % count( $liste ) ], 4, '40 h : 20 CM, 20 TD' );
				$ues[] = array( $liste[ $tour++ % count( $liste ) ], 4, '40 h : 15 CM, 15 TD, 10 TP' );
				$ues[] = array( 2 === $s ? 'Stage d’immersion' : $liste[ $tour++ % count( $liste ) ], 4, 2 === $s ? '4 semaines' : '40 h : 40 TD' );
			}
			foreach ( $ues as $j => $u ) {
				$ue = wp_insert_post( array( 'post_type' => 'ueb_structure', 'post_status' => 'publish', 'post_parent' => $id, 'post_title' => $u[0], 'menu_order' => $ordre++ ) );
				update_post_meta( $ue, '_sueb_code', $code . ( $j + 1 ) );
				update_post_meta( $ue, '_sueb_credits', (string) $u[1] );
				update_post_meta( $ue, '_sueb_semestre', $semestre );
				update_post_meta( $ue, '_sueb_volume', $u[2] );
				update_post_meta( $ue, '_sueb_niveau_ue', $niveau );
				update_post_meta( $ue, '_sueb_demo', 'ue' );
				++$nb_ue;
			}
		}
	}
	echo '.';
}
wp_suspend_cache_invalidation( false );
wp_defer_term_counting( false );
printf( "\n%d textes « À propos » et %d UE d'exemple chargés\n", $nb_texte, $nb_ue );
