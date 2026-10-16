<?php
/**
 * La direction de l'université (PCA, Recteur, Secrétaire Générale) dans le
 * Personnel, catégorie « Direction de l'université », avec leur portrait
 * (assets/images/personnel/<fichier>.webp). Utilisé par installer.php, et à
 * relancer sans risque : une fiche qui existe déjà (même nom) n'est pas recréée.
 * Ensuite, noms, fonctions et photos se changent dans Personnel, dans l'admin.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

function sueb_direction_universite() {
	return array(
		array( 'nom' => 'Jean-Pierre MVONDO EVEZO’O', 'fonction' => 'Président du Conseil d’Administration', 'photo' => 'pca' ),
		array( 'nom' => 'Pr Jean Bosco ETOA ETOA', 'fonction' => 'Recteur', 'photo' => 'recteur' ),
		array( 'nom' => 'Pr Christiane Félicité EWANE épouse ESSOH', 'fonction' => 'Secrétaire Générale', 'photo' => 'sg' ),
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
