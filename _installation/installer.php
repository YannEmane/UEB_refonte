<?php
/**
 * Installation locale du site UEb et contenu de départ.
 * En ligne de commande uniquement, depuis la racine du site :
 *     php wp-content/themes/site-ueb/_installation/installer.php --wordpress <courriel>
 * (installe WordPress), puis la même commande sans --wordpress (contenu).
 *
 * Contenu réel : établissements, rectorat (fonctions), partenariats et
 * actualités tirés de la presse (sources en lien). Les éléments marqués
 * « exemple » (départements, filières, UE) servent à montrer la
 * navigation et sont à supprimer avant la mise en ligne.
 *
 * @package Site_UEB
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = '/site-ueb/';
$installer_wp = in_array( '--wordpress', $argv, true );
if ( $installer_wp ) {
	define( 'WP_INSTALLING', true ); /* le thème ne se charge pas pendant l'installation */
}

$racine = dirname( __DIR__, 4 );
require $racine . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/* ---------- WordPress ---------- */
if ( ! is_blog_installed() ) {
	$mdp = wp_generate_password( 16, false );
	wp_install( "Université d'Ebolowa", 'admin-ueb', end( $argv ), true, '', $mdp, 'fr_FR' );
	echo "Compte admin-ueb créé, mot de passe : $mdp\n";
}
update_option( 'blogdescription', 'The University of Ebolowa' );
update_option( 'WPLANG', 'fr_FR' );
update_option( 'timezone_string', 'Africa/Douala' );
update_option( 'date_format', 'j F Y' );
update_option( 'time_format', 'G\hi' );
update_option( 'permalink_structure', '/%postname%/' );
if ( ! function_exists( 'sueb_corps_personnel' ) ) {
	/* Le thème n'était pas encore actif au chargement de WordPress. */
	switch_theme( 'site-ueb' );
	exit( "WordPress prêt : relancer la commande sans --wordpress pour créer le contenu.\n" );
}
flush_rewrite_rules();

$si_absent = function ( $type, $titre, $parent = 0 ) {
	$deja = get_posts( array( 'post_type' => $type, 'title' => $titre, 'post_parent' => $parent, 'post_status' => 'any', 'numberposts' => 1 ) );
	return $deja ? 0 : 1;
};
$creer = function ( array $p, array $metas = array() ) {
	$id = wp_insert_post( $p + array( 'post_status' => 'publish' ) );
	foreach ( $metas as $k => $v ) {
		update_post_meta( $id, '_sueb_' . $k, $v );
	}
	return $id;
};

/* Copie une image du thème dans la médiathèque (une seule fois). */
function sueb_installer_media( $chemin, $titre ) {
	$nom  = basename( $chemin );
	$deja = get_posts( array( 'post_type' => 'attachment', 'meta_key' => '_sueb_source', 'meta_value' => $nom, 'numberposts' => 1 ) );
	if ( $deja ) {
		return $deja[0]->ID;
	}
	$envoi = wp_upload_bits( $nom, null, file_get_contents( $chemin ) );
	if ( $envoi['error'] ) {
		echo "Image $nom : {$envoi['error']}\n";
		return 0;
	}
	$type = wp_check_filetype( $envoi['file'] );
	$id   = wp_insert_attachment( array( 'post_title' => $titre, 'post_mime_type' => $type['type'], 'post_status' => 'inherit' ), $envoi['file'] );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $envoi['file'] ) );
	update_post_meta( $id, '_sueb_source', $nom );
	return $id;
}
$photo = function ( $nom ) {
	$f = SUEB_DIR . "/assets/images/photos/$nom.webp";
	return sueb_installer_media( file_exists( $f ) ? $f : SUEB_DIR . "/assets/images/photos/$nom.jpg", $nom );
};

/* ---------- Catégories du personnel ---------- */
foreach ( sueb_corps_personnel() as $slug => $nom ) {
	if ( ! term_exists( $slug, 'ueb_corps' ) ) {
		wp_insert_term( $nom, 'ueb_corps', array( 'slug' => $slug ) );
	}
}

/* ---------- Établissements ---------- */
$etabs = array();
$ordre = 0;
foreach ( sueb_etablissements() as $sigle => $e ) {
	$ordre++;
	$deja = get_posts( array( 'post_type' => 'ueb_structure', 'post_parent' => 0, 'meta_key' => '_sueb_sigle', 'meta_value' => $sigle, 'numberposts' => 1 ) );
	$etabs[ $sigle ] = $deja ? $deja[0]->ID : $creer(
		array( 'post_type' => 'ueb_structure', 'post_title' => $e['fr'], 'menu_order' => $ordre, 'post_name' => strtolower( $sigle ) ),
		array( 'sigle' => $sigle, 'ville' => $e['ville'] )
	);
}

/* Exemple de navigation complète sous la FS (à supprimer avant la mise en ligne). */
if ( ! get_children( array( 'post_parent' => $etabs['FS'], 'post_type' => 'ueb_structure' ) ) ) {
	$exemple = array(
		'Département de Mathématiques (exemple)' => array( 'MATH', array(
			'Licence en Mathématiques (exemple)' => array( 'Licence', array(
				array( 'Analyse 1', 'MAT 111', 6, 'S1', '60 h : 30 CM, 24 TD, 6 TP' ),
				array( 'Algèbre linéaire 1', 'MAT 112', 6, 'S1', '60 h : 30 CM, 30 TD' ),
				array( 'Algorithmique et programmation', 'INF 113', 4, 'S1', '45 h : 15 CM, 15 TD, 15 TP' ),
				array( 'Probabilités', 'MAT 121', 5, 'S2', '50 h : 25 CM, 25 TD' ),
			) ),
			'Master en Mathématiques appliquées (exemple)' => array( 'Master', array(
				array( 'Analyse numérique', 'MAT 411', 6, 'S1', '60 h : 30 CM, 15 TD, 15 TP' ),
				array( 'Optimisation', 'MAT 412', 6, 'S1', '60 h : 30 CM, 30 TD' ),
			) ),
		) ),
		'Département de Biologie végétale (exemple)' => array( 'BIOV', array(
			'Licence en Biologie végétale (exemple)' => array( 'Licence', array(
				array( 'Botanique générale', 'BIO 111', 6, 'S1', '60 h : 30 CM, 15 TD, 15 TP' ),
				array( 'Écologie des forêts tropicales', 'BIO 121', 5, 'S2', '50 h : 25 CM, 10 TD, 15 TP' ),
			) ),
		) ),
	);
	$o = 0;
	foreach ( $exemple as $dep => list( $sig, $filieres ) ) {
		$id_dep = $creer( array( 'post_type' => 'ueb_structure', 'post_title' => $dep, 'post_parent' => $etabs['FS'], 'menu_order' => ++$o ), array( 'sigle' => $sig, 'demo' => 1 ) );
		foreach ( $filieres as $fil => list( $diplome, $ues ) ) {
			$id_fil = $creer( array( 'post_type' => 'ueb_structure', 'post_title' => $fil, 'post_parent' => $id_dep ), array( 'diplome' => $diplome, 'demo' => 1 ) );
			foreach ( $ues as $i => $ue ) {
				$creer(
					array( 'post_type' => 'ueb_structure', 'post_title' => $ue[0], 'post_parent' => $id_fil, 'menu_order' => $i + 1, 'post_excerpt' => 'Exemple : objectifs, contenu et modalités d’évaluation de l’UE figureront ici.' ),
					array( 'code' => $ue[1], 'credits' => $ue[2], 'semestre' => $ue[3], 'volume' => $ue[4], 'demo' => 1 )
				);
			}
		}
	}
}

/* ---------- Rectorat ---------- */
if ( ! get_posts( array( 'post_type' => 'ueb_dirigeant', 'numberposts' => 1, 'post_status' => 'any' ) ) ) {
	$o = 0;
	foreach ( sueb_fonctions_rectorat() as $sigle => $titre ) {
		$creer( array( 'post_type' => 'ueb_dirigeant', 'post_title' => 'RECTEUR' === $sigle ? 'Pr Jean Bosco Etoa Etoa' : 'Nom à renseigner', 'menu_order' => ++$o ), array( 'sigle' => $sigle ) );
	}
}

/* ---------- Pages ---------- */
$pages = array(
	'message-du-recteur' => array( 'Message du Recteur', '<p>Le message du Recteur de l’Université d’Ebolowa sera publié ici. Remplacer ce texte par le message transmis par le rectorat.</p>' ),
	'histoire'           => array( 'Histoire de l’Université d’Ebolowa', '<p>L’Université d’Ebolowa a été créée par le décret présidentiel n° 2022/009 du 6 janvier 2022. Son Recteur, le Pr Jean Bosco Etoa Etoa, et le Président de son Conseil d’administration ont été installés le 4 août 2022, date à laquelle l’université a commencé ses activités.</p><p>Elle réunit aujourd’hui neuf établissements répartis entre Ebolowa, Sangmélima, Ambam et Kribi : quatre facultés, une faculté de médecine, deux écoles nationales supérieures, une école supérieure et un institut.</p><p>[Compléter avec l’arrêté ministériel d’organisation et l’historique fourni par le rectorat.]</p>' ),
);
foreach ( $pages as $slug => $p ) {
	if ( ! get_page_by_path( $slug ) ) {
		$creer( array( 'post_type' => 'page', 'post_name' => $slug, 'post_title' => $p[0], 'post_content' => $p[1] ) );
	}
}

/* ---------- Partenariats (sources de presse vérifiées) ---------- */
$partenaires = array(
	array( 'Port Autonome de Kribi', 'Entreprise publique', '2024-11-18', 'https://pak.cm/en/pak-and-the-university-of-ebolowa-sign-a-strategic-partnership-agreement/', 'Convention-cadre signée par le Directeur général du PAK et le Recteur : recherche appliquée dans les secteurs portuaire, logistique et industriel, et insertion professionnelle des étudiants et jeunes diplômés.', 'visite-port', '' ),
	array( 'Centre Pasteur du Cameroun', 'Institut de recherche', '2024-09-09', 'https://cameroon-tribune.cm/article.html/66974/fr.html/details_2', 'Convention signée à Yaoundé : professionnalisation des enseignements, renforcement mutuel des compétences de recherche et projets de recherche multicentriques.', '', 'partenaires/centre-pasteur.png' ),
	array( 'CCA Bank', 'Banque', '2024-06-06', 'https://ecomatin.net/cameroun-cca-bank-sassocie-a-luniversite-debolowa-pour-ameliorer-lacces-aux-services-financiers-en-milieu-estudiantin', 'Convention pour faciliter l’accès des étudiants et du personnel aux services financiers et numériser la gestion financière de l’université.', '', 'partenaires/cca-bank.png' ),
	array( 'Ministère du Tourisme et des Loisirs', 'Ministère', '2026-07-07', 'https://cameroon-tribune.cm/article.html/78601/fr.html/details_2', 'Accord-cadre signé par le ministre et le Recteur pour former des ressources humaines qualifiées dans les métiers du tourisme et de l’hôtellerie.', '', '' ),
	array( 'Institut Sapientia', 'Établissement privé d’enseignement supérieur', '2026-09-08', 'https://cameroon-tribune.cm/article.html/80632/fr.html/details_2', 'Convention de tutelle académique : la FS, la FSEG et la FSJP accompagnent la formation des étudiants de l’institut, établi à Akonolinga.', '', '' ),
);
foreach ( $partenaires as $i => $p ) {
	if ( ! $si_absent( 'ueb_partenaire', $p[0] ) ) {
		continue;
	}
	$id = $creer(
		array( 'post_type' => 'ueb_partenaire', 'post_title' => $p[0], 'post_excerpt' => $p[4], 'post_content' => '<p>' . $p[4] . '</p>', 'menu_order' => $i + 1 ),
		array( 'type' => $p[1], 'signature' => $p[2], 'lien' => $p[3] )
	);
	if ( $p[5] ) {
		set_post_thumbnail( $id, $photo( $p[5] ) );
	}
	if ( $p[6] ) {
		update_post_meta( $id, '_sueb_logo', sueb_installer_media( SUEB_DIR . '/assets/images/' . $p[6], $p[0] ) );
	}
}

/* ---------- Actualités (tirées des mêmes sources) ---------- */
$cat = term_exists( 'Coopération', 'category' ) ?: wp_insert_term( 'Coopération', 'category' );
$actus = array(
	array( '2026-09-08 10:00', 'L’UEb assure la tutelle académique de l’Institut Sapientia', 'Le Recteur, le Pr Jean Bosco Etoa Etoa, et le promoteur de l’Institut Sapientia ont signé le 8 septembre 2026 une convention de tutelle académique, en présence d’un représentant du ministre de l’Enseignement supérieur. La Faculté des Sciences, la FSEG et la FSJP accompagneront la formation des étudiants de cet établissement privé d’Akonolinga.', 'remise-toges', 'https://cameroon-tribune.cm/article.html/80632/fr.html/details_2' ),
	array( '2026-07-07 10:00', 'Tourisme : un accord-cadre avec le ministère du Tourisme et des Loisirs', 'Le ministre du Tourisme et des Loisirs et le Recteur ont signé le 7 juillet 2026 un accord-cadre pour former des ressources humaines qualifiées dans les métiers du tourisme et de l’hôtellerie, dans la perspective de nouvelles formations à l’UEb.', 'diplomes', 'https://cameroon-tribune.cm/article.html/78601/fr.html/details_2' ),
	array( '2024-11-18 10:00', 'Une convention-cadre avec le Port Autonome de Kribi', 'Le Port Autonome de Kribi et l’Université d’Ebolowa ont signé le 18 novembre 2024 une convention-cadre : recherche appliquée dans les secteurs portuaire, logistique et industriel, et débouchés professionnels pour les étudiants, notamment ceux de l’ENSTMO à Kribi.', 'visite-port', 'https://pak.cm/en/pak-and-the-university-of-ebolowa-sign-a-strategic-partnership-agreement/' ),
	array( '2024-09-09 10:00', 'Recherche : l’UEb signe avec le Centre Pasteur du Cameroun', 'Signée à Yaoundé, la convention porte sur la professionnalisation des enseignements, le renforcement mutuel des compétences de recherche et le développement de projets de recherche multicentriques.', 'equipe-fs', 'https://cameroon-tribune.cm/article.html/66974/fr.html/details_2' ),
	array( '2024-06-06 10:00', 'CCA Bank s’associe à l’Université d’Ebolowa', 'La convention vise à faciliter l’accès des étudiants et du personnel aux services financiers et à proposer des solutions numériques de gestion financière au sein de l’université.', 'campus-ebolowa', 'https://ecomatin.net/cameroun-cca-bank-sassocie-a-luniversite-debolowa-pour-ameliorer-lacces-aux-services-financiers-en-milieu-estudiantin' ),
);
foreach ( $actus as $a ) {
	if ( ! $si_absent( 'post', $a[1] ) ) {
		continue;
	}
	$id = $creer( array(
		'post_type'     => 'post',
		'post_title'    => $a[1],
		'post_date'     => $a[0],
		'post_excerpt'  => $a[2],
		'post_content'  => '<p>' . $a[2] . '</p><p><a href="' . esc_url( $a[4] ) . '">Source : article de presse</a></p>',
		'post_category' => array( (int) ( is_array( $cat ) ? $cat['term_id'] : $cat ) ),
	) );
	set_post_thumbnail( $id, $photo( $a[3] ) );
}
wp_delete_post( 1, true ); /* « Bonjour tout le monde » */

/* ---------- Personnel : un responsable par établissement ---------- */
if ( ! get_posts( array( 'post_type' => 'ueb_personnel', 'numberposts' => 1, 'post_status' => 'any' ) ) ) {
	$corps = get_term_by( 'slug', 'doyens-directeurs', 'ueb_corps' );
	foreach ( sueb_etablissements() as $sigle => $e ) {
		$titre = str_starts_with( $e['fr'], 'Faculté' ) ? 'Doyen' : 'Directeur';
		$id    = $creer( array( 'post_type' => 'ueb_personnel', 'post_title' => 'Nom à renseigner' ), array( 'fonction' => $titre . ( str_starts_with( $e['fr'], 'Faculté' ) ? ' de la ' : ' de l’' ) . $e['fr'], 'etablissement' => $etabs[ $sigle ], 'demo' => 1 ) );
		wp_set_object_terms( $id, $corps->term_id, 'ueb_corps' );
	}
}

echo "Installation terminée.\n";
