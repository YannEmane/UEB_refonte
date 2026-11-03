<?php
/**
 * Rôles et comptes de la gestion, sur le modèle de la plateforme d'inscription.
 *
 * Aucun nom de rôle n'est écrit dans le code : on crée, nomme et modifie les rôles
 * depuis /gestion/ (rubrique « Rôles et comptes »). Chaque rôle du registre (option
 * « sueb_roles_registre ») est un vrai rôle WordPress qui ne porte que des
 * permissions de la liste blanche (sueb_permissions()), avec une portée : tous les
 * établissements, celui du compte (méta « sueb_etablissement ») ou une liste fixée
 * dans le rôle.
 *
 * Toute décision d'accès vérifie une PERMISSION et une PORTÉE, jamais un nom de rôle :
 * - les permissions donnent les droits WordPress de leur rubrique (filtre user_has_cap) ;
 * - la portée est vérifiée sur chaque établissement, structure et fiche du personnel
 *   (map_meta_cap, création par l'API, listes de l'API).
 * On n'attribue jamais une permission ni une portée qu'on n'a pas soi-même.
 * Un compte suspendu (méta « sueb_suspendu ») garde ses contenus mais perd tout accès.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

const SUEB_ROLES_VERSION = '1';

/**
 * Liste blanche des permissions, par groupe. « types » : types de contenu dont la
 * permission donne les droits ; « portee » : la permission est limitée aux établissements
 * de la portée du compte.
 */
function sueb_permissions() {
	return array(
		'sueb_actualites'     => array( 'groupe' => 'Contenus', 'libelle' => 'Actualités', 'aide' => 'Écrire, publier et supprimer les articles, vidéos et audios des Actualités, et leurs rubriques.', 'types' => array( 'post' ) ),
		'sueb_evenements'     => array( 'groupe' => 'Contenus', 'libelle' => 'Évènements et agenda', 'aide' => 'Les dates de l’agenda de l’accueil.', 'types' => array( 'ueb_evenement' ) ),
		'sueb_pages'          => array( 'groupe' => 'Contenus', 'libelle' => 'Pages fixes', 'aide' => 'Histoire, message du Recteur, mentions…', 'types' => array( 'page' ) ),
		'sueb_mediatheque'    => array( 'groupe' => 'Contenus', 'libelle' => 'Toute la médiathèque', 'aide' => 'Voir, modifier et supprimer tous les fichiers envoyés. Sans elle, un compte n’envoie et ne modifie que ses propres fichiers.', 'types' => array() ),
		'sueb_etablissements' => array( 'groupe' => 'Établissements', 'libelle' => 'Page de l’établissement', 'aide' => 'Identité, couleur, logo, présentation, photos du bandeau et du campus, textes des sections.', 'types' => array( 'ueb_structure' ), 'portee' => true ),
		'sueb_formations'     => array( 'groupe' => 'Établissements', 'libelle' => 'Départements, filières et UE', 'aide' => 'L’offre de formation de l’établissement, jusqu’aux unités d’enseignement et leurs syllabus.', 'types' => array( 'ueb_structure' ), 'portee' => true ),
		'sueb_personnel'      => array( 'groupe' => 'Établissements', 'libelle' => 'Personnel et annuaire', 'aide' => 'Les fiches du personnel de l’établissement : fonction, grade, parcours, CV.', 'types' => array( 'ueb_personnel' ), 'portee' => true ),
		'sueb_dirigeants'     => array( 'groupe' => 'Université', 'libelle' => 'Dirigeants du rectorat', 'aide' => 'Recteur, vice-recteurs et dirigeants.', 'types' => array( 'ueb_dirigeant' ) ),
		'sueb_partenaires'    => array( 'groupe' => 'Université', 'libelle' => 'Partenaires et carte', 'aide' => 'Conventions, universités partenaires et villes de la carte.', 'types' => array( 'ueb_partenaire' ) ),
		'sueb_reglages'       => array( 'groupe' => 'Université', 'libelle' => 'Accueil et coordonnées', 'aide' => 'Bannière, vidéo et frise de l’accueil, coordonnées et liens du site.', 'types' => array() ),
		'sueb_comptes'        => array( 'groupe' => 'Administration', 'libelle' => 'Rôles et comptes', 'aide' => 'Créer des rôles et des comptes, sans jamais dépasser ses propres droits ni sa portée.', 'types' => array() ),
	);
}

/** Les droits WordPress d'un type de contenu (ceux de register_post_type, capability_type). */
function sueb_droits_type( $type ) {
	$objet = get_post_type_object( $type );
	if ( ! $objet ) {
		return array();
	}
	$c      = (array) $objet->cap;
	$droits = array();
	foreach ( array( 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts' ) as $k ) {
		if ( ! empty( $c[ $k ] ) ) {
			$droits[] = $c[ $k ];
		}
	}
	return array_unique( $droits );
}

/* ---------- Registre des rôles ---------- */

/** Rôles du registre : slug => nom, permissions, portee (tous | un | plusieurs), etablissements (IDs, pour « plusieurs »). */
function sueb_roles() {
	$r = get_option( 'sueb_roles_registre', array() );
	return is_array( $r ) ? $r : array();
}

function sueb_role( $slug ) {
	return sueb_roles()[ $slug ] ?? null;
}

/** Enregistre un rôle du registre et aligne son rôle WordPress : « read » et ses seules permissions. */
function sueb_enregistrer_role( $slug, array $def ) {
	$def = array(
		'nom'            => sanitize_text_field( $def['nom'] ?? '' ),
		'permissions'    => array_values( array_intersect( (array) ( $def['permissions'] ?? array() ), array_keys( sueb_permissions() ) ) ),
		'portee'         => in_array( $def['portee'] ?? '', array( 'tous', 'un', 'plusieurs' ), true ) ? $def['portee'] : 'un',
		'etablissements' => array_values( array_unique( array_map( 'absint', (array) ( $def['etablissements'] ?? array() ) ) ) ),
	);
	if ( 'plusieurs' !== $def['portee'] ) {
		$def['etablissements'] = array();
	}
	$registre          = sueb_roles();
	$registre[ $slug ] = $def;
	update_option( 'sueb_roles_registre', $registre, true );

	$role = get_role( $slug );
	if ( ! $role ) {
		$role = add_role( $slug, $def['nom'], array( 'read' => true ) );
	}
	/* Le rôle ne garde que « read » et ses permissions : les anciens droits stockés (edit_posts…) partent. */
	foreach ( array_keys( $role->capabilities ) as $cap ) {
		if ( 'read' !== $cap && ! in_array( $cap, $def['permissions'], true ) ) {
			$role->remove_cap( $cap );
		}
	}
	$role->add_cap( 'read' );
	foreach ( $def['permissions'] as $cap ) {
		$role->add_cap( $cap );
	}
	$wp_roles = wp_roles();
	if ( ( $wp_roles->roles[ $slug ]['name'] ?? '' ) !== $def['nom'] ) {
		$wp_roles->roles[ $slug ]['name'] = $def['nom'];
		$wp_roles->role_names[ $slug ]    = $def['nom'];
		update_option( $wp_roles->role_key, $wp_roles->roles );
	}
	return $def;
}

/** Nouvel identifiant de rôle, jamais dérivé du nom saisi. */
function sueb_nouveau_slug_role() {
	do {
		$slug = 'sueb_r_' . strtolower( wp_generate_password( 8, false, false ) );
	} while ( sueb_role( $slug ) || get_role( $slug ) );
	return $slug;
}

/* Mise en place (une fois) : l'administrateur reçoit toutes les permissions ; l'ancien rôle
   « Gestionnaire UEb » entre dans le registre, avec tout et pour tous ; un rôle d'exemple
   pour confier la page d'un établissement à son équipe. Aucun compte n'est modifié. */
add_action( 'init', function () {
	if ( get_option( 'sueb_roles_version' ) === SUEB_ROLES_VERSION ) {
		return;
	}
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		foreach ( array_keys( sueb_permissions() ) as $cap ) {
			$admin->add_cap( $cap );
		}
	}
	if ( ! sueb_role( 'gestionnaire_ueb' ) ) {
		sueb_enregistrer_role( 'gestionnaire_ueb', array( 'nom' => 'Gestionnaire du site', 'permissions' => array_keys( sueb_permissions() ), 'portee' => 'tous' ) );
	}
	if ( ! array_filter( sueb_roles(), function ( $d ) { return 'un' === $d['portee']; } ) ) {
		sueb_enregistrer_role( sueb_nouveau_slug_role(), array( 'nom' => 'Responsable de la page d’un établissement', 'permissions' => array( 'sueb_etablissements', 'sueb_formations', 'sueb_personnel' ), 'portee' => 'un' ) );
	}
	update_option( 'sueb_roles_version', SUEB_ROLES_VERSION );
}, 5 );

/* ---------- Permissions et portée d'un compte ---------- */

function sueb_utilisateur( $u = null ) {
	if ( null === $u ) {
		return wp_get_current_user();
	}
	return $u instanceof WP_User ? $u : get_userdata( (int) $u );
}

function sueb_est_suspendu( $u = null ) {
	$u = sueb_utilisateur( $u );
	return $u && $u->exists() && get_user_meta( $u->ID, 'sueb_suspendu', true );
}

/** Les permissions de la liste blanche que porte le compte (toutes pour un administrateur). */
function sueb_permissions_de( $u = null ) {
	$u = sueb_utilisateur( $u );
	if ( ! $u || ! $u->exists() || sueb_est_suspendu( $u ) ) {
		return array();
	}
	if ( ! empty( $u->allcaps['manage_options'] ) ) {
		return array_keys( sueb_permissions() );
	}
	return array_values( array_filter( array_keys( sueb_permissions() ), function ( $cap ) use ( $u ) {
		return ! empty( $u->allcaps[ $cap ] );
	} ) );
}

/** La portée du compte : « tous », ou la liste des établissements (IDs) qu'il gère. */
function sueb_portee( $u = null ) {
	$u = sueb_utilisateur( $u );
	if ( ! $u || ! $u->exists() ) {
		return array();
	}
	if ( ! empty( $u->allcaps['manage_options'] ) ) {
		return 'tous';
	}
	$ids = array();
	foreach ( (array) $u->roles as $slug ) {
		$def = sueb_role( $slug );
		if ( ! $def ) {
			continue;
		}
		if ( 'tous' === $def['portee'] ) {
			return 'tous';
		}
		$ids = array_merge( $ids, 'un' === $def['portee'] ? array( (int) get_user_meta( $u->ID, 'sueb_etablissement', true ) ) : $def['etablissements'] );
	}
	return array_values( array_filter( array_unique( array_map( 'intval', $ids ) ) ) );
}

function sueb_dans_portee( $etab, $u = null ) {
	$p = sueb_portee( $u );
	return 'tous' === $p || in_array( (int) $etab, $p, true );
}

/** L'établissement (structure de premier niveau) d'une structure. */
function sueb_etab_de_structure( $id ) {
	$anc = get_post_ancestors( $id );
	return $anc ? (int) end( $anc ) : (int) $id;
}

/** Peut-on ouvrir la gestion : une permission au moins, compte non suspendu. */
function sueb_peut_gerer( $u = null ) {
	return (bool) sueb_permissions_de( $u );
}

/* ---------- Droits WordPress tirés des permissions ---------- */

add_filter( 'user_has_cap', function ( $allcaps, $caps, $args, $user ) {
	if ( ! empty( $allcaps['manage_options'] ) ) {
		foreach ( sueb_permissions() as $cap => $p ) {
			$allcaps[ $cap ] = true;
			foreach ( $p['types'] as $type ) {
				$allcaps = array_merge( $allcaps, array_fill_keys( sueb_droits_type( $type ), true ) );
			}
		}
		return $allcaps;
	}
	if ( get_user_meta( $user->ID, 'sueb_suspendu', true ) ) {
		return array_fill_keys( array_keys( $allcaps ), false );
	}
	$a_une = false;
	foreach ( sueb_permissions() as $cap => $p ) {
		if ( empty( $allcaps[ $cap ] ) ) {
			continue;
		}
		$a_une = true;
		foreach ( $p['types'] as $type ) {
			$allcaps = array_merge( $allcaps, array_fill_keys( sueb_droits_type( $type ), true ) );
		}
		if ( 'sueb_actualites' === $cap ) {
			$allcaps['manage_categories'] = true;
		}
	}
	if ( $a_une ) {
		/* Tout compte de la gestion envoie ses images et documents (logo, photos, CV, syllabus). */
		$allcaps['upload_files'] = true;
		/* La liste des fichiers de l'API (contexte « edit ») demande edit_posts : accordé pour cette seule route. */
		if ( sueb_requete_rest_media() ) {
			$allcaps['edit_posts'] = true;
		}
	}
	return $allcaps;
}, 10, 4 );

function sueb_requete_rest_media() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	return 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && (bool) preg_match( '#(/wp-json/wp/v2/media|rest_route=/wp/v2/media)#', rawurldecode( $uri ) );
}

/* Contrôle fin, objet par objet : portée des établissements, niveau de la structure, fichiers. */
add_filter( 'map_meta_cap', function ( $caps, $cap, $user_id, $args ) {
	if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'publish_post' ), true ) || empty( $args[0] ) ) {
		return $caps;
	}
	$post = get_post( (int) $args[0] );
	if ( ! $post ) {
		return $caps;
	}
	$u = get_userdata( $user_id );
	if ( ! $u || ! empty( $u->allcaps['manage_options'] ) ) {
		return $caps;
	}
	if ( 'attachment' === $post->post_type ) {
		/* Ses propres fichiers, ou tous avec « Toute la médiathèque ». */
		return ( (int) $post->post_author === (int) $user_id || ! empty( $u->allcaps['sueb_mediatheque'] ) ) ? array( 'upload_files' ) : array( 'do_not_allow' );
	}
	if ( 'ueb_structure' === $post->post_type ) {
		$niveau0 = 0 === (int) $post->post_parent;
		$requise = $niveau0 ? 'sueb_etablissements' : 'sueb_formations';
		if ( empty( $u->allcaps[ $requise ] ) || ! sueb_dans_portee( sueb_etab_de_structure( $post->ID ), $u ) ) {
			return array( 'do_not_allow' );
		}
		/* Supprimer un établissement : réservé à une portée « tous ». */
		if ( $niveau0 && 'delete_post' === $cap && 'tous' !== sueb_portee( $u ) ) {
			return array( 'do_not_allow' );
		}
	}
	if ( 'ueb_personnel' === $post->post_type && ! sueb_dans_portee( (int) get_post_meta( $post->ID, '_sueb_etablissement', true ), $u ) ) {
		return array( 'do_not_allow' );
	}
	return $caps;
}, 10, 4 );

/* Création et déplacement par l'API : la structure doit rester dans la portée, au niveau permis. */
add_filter( 'rest_pre_insert_ueb_structure', function ( $prepare, $requete ) {
	if ( sueb_portee() === 'tous' && current_user_can( 'manage_options' ) ) {
		return $prepare;
	}
	$nouveau = empty( $prepare->ID );
	if ( ! $nouveau && ! isset( $requete['parent'] ) ) {
		return $prepare;
	}
	$parent = (int) ( $prepare->post_parent ?? 0 );
	$perms  = sueb_permissions_de();
	if ( 0 === $parent ) {
		/* Un établissement : seule une portée « tous » en crée ou en fait remonter un. */
		if ( ( $nouveau || 0 !== (int) get_post_field( 'post_parent', $prepare->ID ) ) && ( 'tous' !== sueb_portee() || ! in_array( 'sueb_etablissements', $perms, true ) ) ) {
			return new WP_Error( 'sueb_portee', 'Seul un compte qui gère tous les établissements peut en créer un.', array( 'status' => 403 ) );
		}
		return $prepare;
	}
	if ( ! in_array( 'sueb_formations', $perms, true ) || ! sueb_dans_portee( sueb_etab_de_structure( $parent ) ) ) {
		return new WP_Error( 'sueb_portee', 'Ce rattachement sort des établissements que votre compte gère.', array( 'status' => 403 ) );
	}
	return $prepare;
}, 10, 2 );

/* Fiche du personnel : l'établissement choisi doit être dans la portée. */
add_filter( 'rest_pre_insert_ueb_personnel', function ( $prepare, $requete ) {
	$portee = sueb_portee();
	if ( 'tous' === $portee ) {
		return $prepare;
	}
	$meta = (array) ( $requete['meta'] ?? array() );
	if ( array_key_exists( '_sueb_etablissement', $meta ) ) {
		$etab = (int) $meta['_sueb_etablissement'];
	} else {
		$etab = empty( $prepare->ID ) ? 0 : (int) get_post_meta( $prepare->ID, '_sueb_etablissement', true );
	}
	if ( ! in_array( $etab, $portee, true ) ) {
		return new WP_Error( 'sueb_portee', 'Choisissez un établissement que votre compte gère.', array( 'status' => 403 ) );
	}
	return $prepare;
}, 10, 2 );

/* L'établissement (vérifié ci-dessus) est posé dès la création : les métas, enregistrées ensuite,
   sont contrôlées par edit_post, qui a besoin de savoir à quel établissement la fiche appartient. */
add_action( 'rest_insert_ueb_personnel', function ( $post, $requete ) {
	$meta = (array) ( $requete['meta'] ?? array() );
	if ( array_key_exists( '_sueb_etablissement', $meta ) ) {
		$etab = absint( $meta['_sueb_etablissement'] );
		$etab ? update_post_meta( $post->ID, '_sueb_etablissement', $etab ) : delete_post_meta( $post->ID, '_sueb_etablissement' );
	}
}, 10, 2 );

/* Listes de l'API (gestion) : un compte limité ne voit que les établissements de sa portée. */
add_filter( 'rest_ueb_structure_query', function ( $args, $requete ) {
	$portee = sueb_portee();
	if ( 'tous' === $portee || 'edit' !== $requete['context'] ) {
		return $args;
	}
	global $wpdb;
	$parents = $wpdb->get_results( "SELECT ID, post_parent FROM {$wpdb->posts} WHERE post_type = 'ueb_structure'", OBJECT_K ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$permis  = array();
	foreach ( $parents as $id => $p ) {
		$racine = (int) $id;
		for ( $n = 0; $n < 10 && ! empty( $parents[ $racine ] ) && $parents[ $racine ]->post_parent; $n++ ) {
			$racine = (int) $parents[ $racine ]->post_parent;
		}
		if ( in_array( $racine, $portee, true ) ) {
			$permis[] = (int) $id;
		}
	}
	$args['post__in'] = empty( $args['post__in'] ) ? ( $permis ?: array( 0 ) ) : ( array_intersect( $args['post__in'], $permis ) ?: array( 0 ) );
	return $args;
}, 10, 2 );

add_filter( 'rest_ueb_personnel_query', function ( $args, $requete ) {
	$portee = sueb_portee();
	if ( 'tous' === $portee || 'edit' !== $requete['context'] ) {
		return $args;
	}
	$args['meta_query']   = (array) ( $args['meta_query'] ?? array() );
	$args['meta_query'][] = array( 'key' => '_sueb_etablissement', 'value' => $portee ?: array( -1 ), 'compare' => 'IN' );
	return $args;
}, 10, 2 );

/* ---------- Rôles et comptes : ce qu'un compte peut attribuer ---------- */

/** Un rôle reste-t-il dans les droits et la portée du compte courant ? */
function sueb_role_attribuable( array $def, $etab_compte = 0 ) {
	if ( array_diff( $def['permissions'], sueb_permissions_de() ) ) {
		return false;
	}
	$portee = sueb_portee();
	if ( 'tous' === $portee ) {
		return true;
	}
	if ( 'tous' === $def['portee'] ) {
		return false;
	}
	if ( 'plusieurs' === $def['portee'] ) {
		return ! array_diff( $def['etablissements'], $portee );
	}
	return ! $etab_compte || in_array( (int) $etab_compte, $portee, true );
}

/** Rôle du registre porté par un compte (le premier). */
function sueb_role_du_compte( WP_User $u ) {
	foreach ( (array) $u->roles as $slug ) {
		if ( sueb_role( $slug ) ) {
			return $slug;
		}
	}
	return '';
}

/** Le compte courant peut-il gérer ce compte : pas un administrateur, pas lui-même, un rôle qu'il pourrait attribuer. */
function sueb_compte_gerable( WP_User $u ) {
	if ( ! empty( $u->allcaps['manage_options'] ) || get_current_user_id() === $u->ID ) {
		return false;
	}
	$slug = sueb_role_du_compte( $u );
	return $slug && sueb_role_attribuable( sueb_role( $slug ), (int) get_user_meta( $u->ID, 'sueb_etablissement', true ) );
}

function sueb_etablissements_liste() {
	$liste = array();
	foreach ( get_posts( array( 'post_type' => 'ueb_structure', 'post_parent' => 0, 'post_status' => 'any', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) ) as $e ) {
		$liste[] = array( 'id' => $e->ID, 'sigle' => (string) get_post_meta( $e->ID, '_sueb_sigle', true ), 'nom' => $e->post_title );
	}
	return $liste;
}

function sueb_compte_json( WP_User $u ) {
	$slug = sueb_role_du_compte( $u );
	return array(
		'id'            => $u->ID,
		'nom'           => $u->display_name,
		'identifiant'   => $u->user_login,
		'courriel'      => $u->user_email,
		'role'          => $slug,
		'admin'         => ! empty( $u->allcaps['manage_options'] ),
		'etablissement' => (int) get_user_meta( $u->ID, 'sueb_etablissement', true ),
		'suspendu'      => (bool) get_user_meta( $u->ID, 'sueb_suspendu', true ),
		'moi'           => get_current_user_id() === $u->ID,
		'gerable'       => sueb_compte_gerable( $u ),
	);
}

function sueb_peut_gerer_comptes() {
	return in_array( 'sueb_comptes', sueb_permissions_de(), true );
}

/** Lit et vérifie une définition de rôle envoyée par la gestion. */
function sueb_lire_role( array $p ) {
	$def = array(
		'nom'            => trim( sanitize_text_field( $p['nom'] ?? '' ) ),
		'permissions'    => array_values( array_intersect( (array) ( $p['permissions'] ?? array() ), array_keys( sueb_permissions() ) ) ),
		'portee'         => in_array( $p['portee'] ?? '', array( 'tous', 'un', 'plusieurs' ), true ) ? $p['portee'] : 'un',
		'etablissements' => array_values( array_filter( array_map( 'absint', (array) ( $p['etablissements'] ?? array() ) ) ) ),
	);
	if ( '' === $def['nom'] ) {
		return new WP_Error( 'sueb_role', 'Donnez un nom au rôle.', array( 'status' => 400 ) );
	}
	if ( ! $def['permissions'] ) {
		return new WP_Error( 'sueb_role', 'Cochez au moins une permission.', array( 'status' => 400 ) );
	}
	if ( 'plusieurs' === $def['portee'] && ! $def['etablissements'] ) {
		return new WP_Error( 'sueb_role', 'Choisissez les établissements de ce rôle.', array( 'status' => 400 ) );
	}
	if ( ! sueb_role_attribuable( $def ) ) {
		return new WP_Error( 'sueb_role', 'Ce rôle dépasserait vos propres droits ou votre portée.', array( 'status' => 403 ) );
	}
	return $def;
}

add_action( 'rest_api_init', function () {
	$ns = 'sueb/v1';

	register_rest_route( $ns, '/comptes', array(
		array(
			'methods'             => 'GET',
			'permission_callback' => 'sueb_peut_gerer_comptes',
			'callback'            => function () {
				$roles = array();
				foreach ( sueb_roles() as $slug => $def ) {
					$roles[] = $def + array(
						'slug'        => $slug,
						'comptes'     => count( get_users( array( 'role' => $slug, 'fields' => 'ID' ) ) ),
						'attribuable' => sueb_role_attribuable( $def ),
					);
				}
				$comptes = array();
				foreach ( get_users( array( 'orderby' => 'display_name' ) ) as $u ) {
					$c = sueb_compte_json( $u );
					/* Un compte limité ne voit que les comptes qu'il gère, et le sien. */
					if ( 'tous' === sueb_portee() || $c['gerable'] || $c['moi'] ) {
						$comptes[] = $c;
					}
				}
				$definitions = array();
				foreach ( sueb_permissions() as $cap => $p ) {
					$definitions[] = array( 'cap' => $cap, 'groupe' => $p['groupe'], 'libelle' => $p['libelle'], 'aide' => $p['aide'], 'portee' => ! empty( $p['portee'] ) );
				}
				return array(
					'roles'          => $roles,
					'comptes'        => $comptes,
					'permissions'    => $definitions,
					'etablissements' => sueb_etablissements_liste(),
					'moi'            => array( 'permissions' => sueb_permissions_de(), 'portee' => sueb_portee() ),
				);
			},
		),
		array(
			'methods'             => 'POST',
			'permission_callback' => 'sueb_peut_gerer_comptes',
			'callback'            => 'sueb_rest_enregistrer_compte',
		),
	) );

	register_rest_route( $ns, '/comptes/(?P<id>\d+)/(?P<action>suspendre|reactiver|mdp)', array(
		'methods'             => 'POST',
		'permission_callback' => 'sueb_peut_gerer_comptes',
		'callback'            => function ( WP_REST_Request $r ) {
			$u = get_userdata( (int) $r['id'] );
			if ( ! $u || ! sueb_compte_gerable( $u ) ) {
				return new WP_Error( 'sueb_compte', 'Ce compte n’est pas dans ceux que vous gérez.', array( 'status' => 403 ) );
			}
			if ( 'mdp' === $r['action'] ) {
				$mdp = wp_generate_password( 14, false );
				wp_set_password( $mdp, $u->ID );
				return array( 'mdp' => $mdp );
			}
			if ( 'suspendre' === $r['action'] ) {
				update_user_meta( $u->ID, 'sueb_suspendu', 1 );
				WP_Session_Tokens::get_instance( $u->ID )->destroy_all();
			} else {
				delete_user_meta( $u->ID, 'sueb_suspendu' );
			}
			return sueb_compte_json( get_userdata( $u->ID ) );
		},
	) );

	register_rest_route( $ns, '/roles', array(
		'methods'             => 'POST',
		'permission_callback' => 'sueb_peut_gerer_comptes',
		'callback'            => function ( WP_REST_Request $r ) {
			$p    = (array) $r->get_json_params();
			$slug = sanitize_key( $p['slug'] ?? '' );
			if ( $slug ) {
				$actuel = sueb_role( $slug );
				if ( ! $actuel || ! sueb_role_attribuable( $actuel ) ) {
					return new WP_Error( 'sueb_role', 'Ce rôle dépasse vos droits : vous ne pouvez pas le modifier.', array( 'status' => 403 ) );
				}
				if ( in_array( $slug, (array) wp_get_current_user()->roles, true ) ) {
					return new WP_Error( 'sueb_role', 'Vous ne pouvez pas modifier votre propre rôle.', array( 'status' => 403 ) );
				}
			}
			$def = sueb_lire_role( $p );
			if ( is_wp_error( $def ) ) {
				return $def;
			}
			$slug = $slug ?: sueb_nouveau_slug_role();
			return sueb_enregistrer_role( $slug, $def ) + array( 'slug' => $slug );
		},
	) );

	register_rest_route( $ns, '/roles/(?P<slug>[a-z0-9_]+)', array(
		'methods'             => 'DELETE',
		'permission_callback' => 'sueb_peut_gerer_comptes',
		'callback'            => function ( WP_REST_Request $r ) {
			$slug = sanitize_key( $r['slug'] );
			$def  = sueb_role( $slug );
			if ( ! $def || ! sueb_role_attribuable( $def ) ) {
				return new WP_Error( 'sueb_role', 'Ce rôle dépasse vos droits.', array( 'status' => 403 ) );
			}
			if ( get_users( array( 'role' => $slug, 'number' => 1, 'fields' => 'ID' ) ) ) {
				return new WP_Error( 'sueb_role', 'Des comptes portent encore ce rôle : donnez-leur d’abord un autre rôle.', array( 'status' => 409 ) );
			}
			$registre = sueb_roles();
			unset( $registre[ $slug ] );
			update_option( 'sueb_roles_registre', $registre, true );
			remove_role( $slug );
			return array( 'ok' => true );
		},
	) );
} );

/** Création ou modification d'un compte ; à la création, le mot de passe provisoire est renvoyé une seule fois. */
function sueb_rest_enregistrer_compte( WP_REST_Request $r ) {
	$p    = (array) $r->get_json_params();
	$id   = absint( $p['id'] ?? 0 );
	$slug = sanitize_key( $p['role'] ?? '' );
	$def  = sueb_role( $slug );
	if ( ! $def ) {
		return new WP_Error( 'sueb_compte', 'Choisissez un rôle.', array( 'status' => 400 ) );
	}
	$etab = 'un' === $def['portee'] ? absint( $p['etablissement'] ?? 0 ) : 0;
	if ( 'un' === $def['portee'] && ! $etab ) {
		return new WP_Error( 'sueb_compte', 'Ce rôle agit sur un établissement : choisissez lequel.', array( 'status' => 400 ) );
	}
	if ( ! sueb_role_attribuable( $def, $etab ) ) {
		return new WP_Error( 'sueb_compte', 'Ce rôle ou cet établissement dépasse vos propres droits.', array( 'status' => 403 ) );
	}
	$nom      = trim( sanitize_text_field( $p['nom'] ?? '' ) );
	$courriel = sanitize_email( $p['courriel'] ?? '' );
	if ( '' === $nom || ! is_email( $courriel ) ) {
		return new WP_Error( 'sueb_compte', 'Indiquez le nom et une adresse électronique valide.', array( 'status' => 400 ) );
	}
	$mdp = '';
	if ( $id ) {
		$u = get_userdata( $id );
		if ( ! $u || ! sueb_compte_gerable( $u ) ) {
			return new WP_Error( 'sueb_compte', 'Ce compte n’est pas dans ceux que vous gérez.', array( 'status' => 403 ) );
		}
		$autre = email_exists( $courriel );
		if ( $autre && (int) $autre !== $id ) {
			return new WP_Error( 'sueb_compte', 'Cette adresse est déjà utilisée par un autre compte.', array( 'status' => 409 ) );
		}
		$res = wp_update_user( array( 'ID' => $id, 'display_name' => $nom, 'user_email' => $courriel ) );
	} else {
		$identifiant = sanitize_user( $p['identifiant'] ?? '', true );
		if ( '' === $identifiant || username_exists( $identifiant ) ) {
			return new WP_Error( 'sueb_compte', '' === $identifiant ? 'Choisissez un identifiant.' : 'Cet identifiant est déjà pris.', array( 'status' => 409 ) );
		}
		if ( email_exists( $courriel ) ) {
			return new WP_Error( 'sueb_compte', 'Cette adresse est déjà utilisée par un autre compte.', array( 'status' => 409 ) );
		}
		$mdp = wp_generate_password( 14, false );
		$res = wp_insert_user( array( 'user_login' => $identifiant, 'user_pass' => $mdp, 'user_email' => $courriel, 'display_name' => $nom, 'role' => $slug ) );
	}
	if ( is_wp_error( $res ) ) {
		return new WP_Error( 'sueb_compte', $res->get_error_message(), array( 'status' => 400 ) );
	}
	$u = get_userdata( (int) $res );
	$u->set_role( $slug );
	if ( $etab ) {
		update_user_meta( $u->ID, 'sueb_etablissement', $etab );
	} else {
		delete_user_meta( $u->ID, 'sueb_etablissement' );
	}
	return array( 'compte' => sueb_compte_json( get_userdata( $u->ID ) ), 'mdp' => $mdp );
}
