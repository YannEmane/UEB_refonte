<?php
/**
 * Les données du tableau de bord de la gestion (assets/js/admin.js, tableauDeBord()), en une requête :
 * sueb/v1/tableau-de-bord.
 *
 *  - chiffres : personnel, filières, établissements, départements ;
 *  - filieres_par_etablissement : l'histogramme ;
 *  - par_type : les filières par type de formation (une filière peut en avoir deux : BTS puis licence pro) ;
 *  - completude : la part des contenus déjà renseignés (les données de test ne comptent pas) ;
 *  - visites : les 30 derniers jours (inc/visites.php).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	register_rest_route( 'sueb/v1', '/tableau-de-bord', array(
		'methods'             => 'GET',
		'permission_callback' => 'sueb_peut_gerer',
		'callback'            => 'sueb_donnees_tableau_de_bord',
	) );
} );

function sueb_donnees_tableau_de_bord() {
	global $wpdb;
	$publies = function ( $type ) {
		return (int) wp_count_posts( $type )->publish;
	};
	/* Les structures par leur clé : « fil:… » pour une filière, « dep:… » pour un département ou un groupe. */
	$par_cle = function ( $prefixe, $sans_groupe = false ) use ( $wpdb ) {
		$sql = "SELECT COUNT(*) FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id AND p.post_status = 'publish'
			WHERE m.meta_key = '_sueb_cle' AND m.meta_value LIKE %s";
		if ( $sans_groupe ) {
			$sql .= " AND NOT EXISTS ( SELECT 1 FROM {$wpdb->postmeta} g WHERE g.post_id = p.ID AND g.meta_key = '_sueb_groupe' AND g.meta_value = '1' )";
		}
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $prefixe . '%' ) ); // phpcs:ignore WordPress.DB
	};

	/* L'histogramme : les filières de chaque établissement, dans l'ordre de l'accueil. */
	$ordre = array_keys( sueb_etablissements() );
	$etabs = array();
	foreach ( sueb_arbre_formation() as $e ) {
		$sigle = strtoupper( $e['sigle'] );
		$etabs[ $sigle ] = array(
			'sigle'    => $sigle,
			'nom'      => $e['titre'],
			'filieres' => sueb_compter( $e, 2 ),
		);
	}
	uksort( $etabs, function ( $a, $b ) use ( $ordre ) {
		$ia = array_search( $a, $ordre, true );
		$ib = array_search( $b, $ordre, true );
		return ( false === $ia ? 99 : $ia ) <=> ( false === $ib ? 99 : $ib );
	} );

	/* Les types de formation (parcours) : le nombre de filières distinctes de chacun. */
	$types = array();
	foreach ( sueb_offre_par_parcours() as $cle => $par_sigle ) {
		$ids = array();
		foreach ( $par_sigle as $choix ) {
			$ids = array_merge( $ids, array_column( $choix['filieres'], 'id' ) );
		}
		$types[] = array( 'cle' => $cle, 'nom' => sueb_parcours()[ $cle ]['nom'], 'filieres' => count( array_unique( $ids ) ) );
	}

	/* La complétude : ce qui est vraiment renseigné, sans les données de test (méta _sueb_demo*). */
	$filieres = $par_cle( 'fil:' );
	$avec_presentation = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id AND p.post_status = 'publish'
		WHERE m.meta_key = '_sueb_cle' AND m.meta_value LIKE 'fil:%' AND TRIM(p.post_content) <> ''
		AND NOT EXISTS ( SELECT 1 FROM {$wpdb->postmeta} d WHERE d.post_id = p.ID AND d.meta_key = '_sueb_demo_apropos' )" ); // phpcs:ignore WordPress.DB
	$avec_programme = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT f.ID) FROM {$wpdb->posts} f
		JOIN {$wpdb->postmeta} m ON m.post_id = f.ID AND m.meta_key = '_sueb_cle' AND m.meta_value LIKE 'fil:%'
		JOIN {$wpdb->posts} u ON u.post_parent = f.ID AND u.post_type = 'ueb_structure' AND u.post_status = 'publish'
		WHERE f.post_status = 'publish'
		AND NOT EXISTS ( SELECT 1 FROM {$wpdb->postmeta} d WHERE d.post_id = u.ID AND d.meta_key = '_sueb_demo' )" ); // phpcs:ignore WordPress.DB
	$personnel = $publies( 'ueb_personnel' );
	$avec_photo = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} t ON t.post_id = p.ID AND t.meta_key = '_thumbnail_id' AND t.meta_value > 0
		WHERE p.post_type = 'ueb_personnel' AND p.post_status = 'publish'" ); // phpcs:ignore WordPress.DB
	$articles = $publies( 'post' );
	$articles_image = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} t ON t.post_id = p.ID AND t.meta_key = '_thumbnail_id' AND t.meta_value > 0
		WHERE p.post_type = 'post' AND p.post_status = 'publish'" ); // phpcs:ignore WordPress.DB

	$visites = sueb_visites();
	ksort( $visites );

	return array(
		'chiffres'                   => array(
			'personnel'      => $personnel,
			'filieres'       => $filieres,
			'etablissements' => count( $etabs ),
			'departements'   => $par_cle( 'dep:', true ),
		),
		'filieres_par_etablissement' => array_values( $etabs ),
		'par_type'                   => $types,
		'completude'                 => array(
			array( 'libelle' => 'Filières présentées', 'aide' => 'texte « À propos » saisi', 'valeur' => $avec_presentation, 'total' => $filieres, 'lien' => 'filieres' ),
			array( 'libelle' => 'Programmes en ligne', 'aide' => 'filières avec leurs UE', 'valeur' => $avec_programme, 'total' => $filieres, 'lien' => 'filieres' ),
			array( 'libelle' => 'Portraits du personnel', 'aide' => 'fiches avec une photo', 'valeur' => $avec_photo, 'total' => $personnel, 'lien' => 'personnel' ),
			array( 'libelle' => 'Actualités illustrées', 'aide' => 'articles avec une image', 'valeur' => $articles_image, 'total' => $articles, 'lien' => 'actualites' ),
		),
		'visites'                    => sueb_visites_recentes( 30 ),
		'visites_depuis'             => $visites ? array_key_first( $visites ) : '',
	);
}
