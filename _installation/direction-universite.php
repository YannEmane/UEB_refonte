<?php
/**
 * La direction de l'université (rectorat) dans le Personnel, catégorie
 * « Direction de l'université », dans l'ordre de l'organigramme de l'accueil
 * (le pôle de chacun se déduit de sa fonction), avec leur portrait
 * (assets/images/personnel/<fichier>.webp). Utilisé par installer.php, et à
 * relancer sans risque : une fiche qui existe déjà (même nom) n'est pas recréée.
 * Ensuite, noms, fonctions et photos se changent dans Personnel, dans l'admin.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

function sueb_direction_universite() {
	return array(
		/* Conseil d'administration et Rectorat. */
		array( 'nom' => 'Pr Jean Bosco ETOA ETOA', 'fonction' => 'Recteur', 'photo' => 'recteur' ),
		array( 'nom' => 'M. Jean-Pierre MVONDO EVEZO’O', 'fonction' => 'Président du Conseil d’Administration', 'photo' => 'pca' ),
		/* Vice-Rectorat. */
		array( 'nom' => 'Pr Robert KPWANG KPWANG', 'fonction' => 'Vice-Recteur chargé des Enseignements, de la Professionnalisation et du Développement des TIC', 'photo' => 'kpwang' ),
		array( 'nom' => 'Pr Jean BIWOLE FOUDA', 'fonction' => 'Vice-Recteur chargé de la Recherche, de la Coopération et des Relations avec le Monde des Entreprises', 'photo' => 'biwole' ),
		array( 'nom' => 'Pr Christophe MEZUI', 'fonction' => 'Vice-Recteur chargé du Contrôle interne et de l’Évaluation', 'photo' => 'mezui' ),
		/* Secrétariat général et Cabinet du Recteur. */
		array( 'nom' => 'Pr Christiane Félicité EWANE épouse ESSOH', 'fonction' => 'Secrétaire Générale', 'photo' => 'sg' ),
		array( 'nom' => 'Pr Raymond MBASSI ATEBA', 'fonction' => 'Conseiller Technique', 'photo' => 'mbassi' ),
		/* Directions centrales. */
		array( 'nom' => 'Pr Stève Thiery BILOUNGA', 'fonction' => 'Directeur des Affaires Académiques et de la Coopération', 'photo' => 'bilounga' ),
		array( 'nom' => 'Dr Jean Patrice AKAM AKAM', 'fonction' => 'Directeur des Affaires Administratives et Financières', 'photo' => 'akam' ),
		array( 'nom' => 'M. Hervé ESSIANE MEDJO', 'fonction' => 'Directeur des Infrastructures, de la Planification et du Développement', 'photo' => 'essiane' ),
		array( 'nom' => 'Mme Chanty Violette MEDJA EKOTO', 'fonction' => 'Directrice du Centre des Œuvres Universitaires', 'photo' => 'medja' ),
	);
}

function sueb_appliquer_direction() {
	if ( ! term_exists( 'direction', 'ueb_corps' ) ) {
		wp_insert_term( sueb_corps_personnel()['direction'], 'ueb_corps', array( 'slug' => 'direction' ) );
	}
	$corps = get_term_by( 'slug', 'direction', 'ueb_corps' );
	foreach ( sueb_direction_universite() as $i => $d ) {
		if ( get_posts( array( 'post_type' => 'ueb_personnel', 'title' => $d['nom'], 'post_status' => 'any', 'numberposts' => 1 ) ) ) {
			continue;
		}
		$id = wp_insert_post( array( 'post_type' => 'ueb_personnel', 'post_title' => $d['nom'], 'post_status' => 'publish', 'menu_order' => $i + 1 ) );
		update_post_meta( $id, '_sueb_fonction', $d['fonction'] );
		wp_set_object_terms( $id, $corps->term_id, 'ueb_corps' );
		$portrait = SUEB_DIR . '/assets/images/personnel/' . $d['photo'] . '.webp';
		if ( file_exists( $portrait ) ) {
			set_post_thumbnail( $id, sueb_installer_media( $portrait, $d['nom'] ) );
		}
		echo "Direction : {$d['nom']} ajouté.\n";
	}
}
