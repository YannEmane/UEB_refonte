<?php
/**
 * Types de contenu : dirigeants, offre de formation (établissement >
 * département > filière > UE), partenaires, évènements, personnel.
 * Les actualités sont les articles WordPress (formats vidéo et audio).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	$etiquettes = function ( $un, $plusieurs, $feminin = false ) {
		$nouveau = $feminin ? 'Nouvelle' : 'Nouveau';
		return array(
			'name'               => $plusieurs,
			'singular_name'      => $un,
			'add_new'            => 'Ajouter',
			'add_new_item'       => "$nouveau " . mb_strtolower( $un ),
			'edit_item'          => 'Modifier : ' . mb_strtolower( $un ),
			'all_items'          => "Tous les " . mb_strtolower( $plusieurs ),
			'search_items'       => 'Rechercher',
			'not_found'          => 'Aucun élément',
			'featured_image'     => 'Photo',
			'set_featured_image' => 'Choisir la photo',
			'remove_featured_image' => 'Retirer la photo',
			'use_featured_image' => 'Utiliser comme photo',
		);
	};

	register_post_type( 'ueb_dirigeant', array(
		'labels'        => array_merge( $etiquettes( 'Dirigeant', 'Dirigeants du rectorat' ), array( 'all_items' => 'Dirigeants', 'enter_title_here' => 'Nom complet (ex. Pr Jean Dupont)' ) ),
		'public'        => false,
		'show_ui'       => true,
		'menu_icon'     => 'dashicons-groups',
		'menu_position' => 20,
		'supports'      => array( 'title', 'thumbnail', 'page-attributes', 'editor' ),
		'show_in_rest'  => true,
	) );

	register_post_type( 'ueb_structure', array(
		'labels'        => array_merge( $etiquettes( 'Structure', 'Offre de formation', true ), array(
			'all_items'        => 'Établissements, départements…',
			'add_new_item'     => 'Ajouter un établissement, un département, une filière ou une UE',
			'parent_item_colon'=> 'Rattaché à :',
			'enter_title_here' => 'Intitulé',
		) ),
		'public'        => true,
		'hierarchical'  => true,
		'menu_icon'     => 'dashicons-building',
		'menu_position' => 21,
		'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
		'rewrite'       => array( 'slug' => 'etablissements', 'with_front' => false ),
		'has_archive'   => 'etablissements',
		'show_in_rest'  => true,
	) );

	register_post_type( 'ueb_partenaire', array(
		'labels'        => $etiquettes( 'Partenaire', 'Partenariats' ),
		'public'        => true,
		'menu_icon'     => 'dashicons-admin-links',
		'menu_position' => 22,
		'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
		'rewrite'       => array( 'slug' => 'partenariats', 'with_front' => false ),
		'has_archive'   => 'partenariats',
		'show_in_rest'  => true,
	) );

	register_post_type( 'ueb_evenement', array(
		'labels'        => $etiquettes( 'Évènement', 'Évènements' ),
		'public'        => true,
		'menu_icon'     => 'dashicons-calendar-alt',
		'menu_position' => 6,
		'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
		'rewrite'       => array( 'slug' => 'evenements', 'with_front' => false ),
		'has_archive'   => 'evenements',
		'show_in_rest'  => true,
	) );

	register_post_type( 'ueb_personnel', array(
		'labels'        => array_merge( $etiquettes( 'Membre du personnel', 'Personnel' ), array( 'all_items' => 'Tout le personnel', 'enter_title_here' => 'Nom complet' ) ),
		'public'        => true,
		'menu_icon'     => 'dashicons-id',
		'menu_position' => 23,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		'rewrite'       => array( 'slug' => 'personnel', 'with_front' => false ),
		'has_archive'   => 'personnel',
		'show_in_rest'  => true,
	) );

	register_taxonomy( 'ueb_corps', 'ueb_personnel', array(
		'labels'            => array( 'name' => 'Catégories du personnel', 'singular_name' => 'Catégorie', 'add_new_item' => 'Ajouter une catégorie' ),
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'personnel/categorie' ),
	) );
} );

/* Les actualités : « Articles » s'appelle « Actualités » dans l'admin. */
add_action( 'admin_menu', function () {
	global $menu;
	foreach ( $menu as $i => $item ) {
		if ( 'edit.php' === $item[2] ) {
			$menu[ $i ][0] = 'Actualités';
		}
	}
} );

/** Profondeur d'une structure : 0 établissement, 1 département, 2 filière, 3 UE. */
function sueb_profondeur( $post ) {
	return count( get_post_ancestors( $post ) );
}

/** L'établissement (racine) d'une structure. */
function sueb_racine( $post ) {
	$anc = get_post_ancestors( $post );
	return $anc ? (int) end( $anc ) : (int) ( is_object( $post ) ? $post->ID : $post );
}

/* Colonnes utiles dans les listes de l'admin. */
add_filter( 'manage_ueb_structure_posts_columns', function ( $c ) {
	return array_slice( $c, 0, 2 ) + array( 'niveau' => 'Niveau', 'credits' => 'Crédits' ) + $c;
} );
add_action( 'manage_ueb_structure_posts_custom_column', function ( $col, $id ) {
	if ( 'niveau' === $col ) {
		echo esc_html( sueb_niveaux()[ min( 3, sueb_profondeur( $id ) ) ]['un'] );
	} elseif ( 'credits' === $col ) {
		echo esc_html( sueb_meta( $id, 'credits' ) ?: '—' );
	}
}, 10, 2 );

add_filter( 'manage_ueb_dirigeant_posts_columns', function ( $c ) {
	return array_slice( $c, 0, 1 ) + array( 'photo' => 'Photo' ) + array_slice( $c, 1, 1 ) + array( 'sigle' => 'Fonction', 'ordre' => 'Ordre' ) + $c;
} );
add_action( 'manage_ueb_dirigeant_posts_custom_column', function ( $col, $id ) {
	if ( 'photo' === $col ) {
		echo get_the_post_thumbnail( $id, array( 48, 48 ), array( 'style' => 'border-radius:50%;object-fit:cover;width:48px;height:48px' ) ) ?: '—';
	} elseif ( 'sigle' === $col ) {
		echo esc_html( sueb_meta( $id, 'sigle' ) );
	} elseif ( 'ordre' === $col ) {
		echo (int) get_post_field( 'menu_order', $id );
	}
}, 10, 2 );

/* Les dirigeants se listent dans l'ordre protocolaire. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && in_array( $q->get( 'post_type' ), array( 'ueb_dirigeant', 'ueb_partenaire', 'ueb_personnel' ), true ) && ! $q->get( 'orderby' ) ) {
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	}
} );
