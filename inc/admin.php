<?php
/**
 * Partie administrateur : la page « /gestion/ », une page du site protégée par
 * connexion, depuis laquelle le gestionnaire ajoute, modifie et supprime les
 * contenus. L'interface (assets/js/admin.js) parle à l'API REST de WordPress ;
 * ce fichier prépare la route, le rôle, la connexion et les champs exposés.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Rôle et droits ----------
   Rôles, permissions et portée : inc/roles.php (sueb_peut_gerer(), sueb_permissions_de(), sueb_portee()). */

function sueb_url_gestion() {
	return home_url( '/gestion/' );
}

/** Le gestionnaire travaille sur le site : pas de barre d'administration, ni d'accès à l'admin WordPress. */
function sueb_est_gestionnaire_seul() {
	return is_user_logged_in() && ! current_user_can( 'manage_options' ) && sueb_peut_gerer();
}
add_filter( 'show_admin_bar', function ( $afficher ) {
	return sueb_est_gestionnaire_seul() ? false : $afficher;
} );
add_action( 'admin_init', function () {
	if ( ! wp_doing_ajax() && sueb_est_gestionnaire_seul() ) {
		wp_safe_redirect( sueb_url_gestion() );
		exit;
	}
} );

/* ---------- La route /gestion/ ---------- */

add_action( 'init', function () {
	add_rewrite_rule( '^gestion/?$', 'index.php?sueb_gestion=1', 'top' );
	if ( 'g1' !== get_option( 'sueb_regles_gestion' ) ) {
		flush_rewrite_rules( false );
		update_option( 'sueb_regles_gestion', 'g1' );
	}
} );

/* Reconnaît /gestion et /gestion/ directement dans l'adresse demandée (avec ou sans barre finale,
   dans un sous-dossier ou non) : la page ne dépend plus des règles de réécriture enregistrées. */
add_action( 'parse_request', function ( $wp ) {
	if ( 'gestion' === trim( (string) $wp->request, '/' ) ) {
		$wp->query_vars  = array( 'sueb_gestion' => '1' );
		$wp->matched_rule = '';
	}
} );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'sueb_gestion';
	return $vars;
} );

/* Sans contenu associé, WordPress traite la route comme une 404, puis « devine » une adresse proche
   (ici une filière dont l'adresse commence par « gestion »). On l'en empêche pour cette route. */
add_filter( 'pre_handle_404', function ( $court_circuit, $requete ) {
	return $requete->get( 'sueb_gestion' ) ? true : $court_circuit;
}, 10, 2 );
add_filter( 'redirect_canonical', function ( $url ) {
	return get_query_var( 'sueb_gestion' ) ? false : $url;
} );

add_filter( 'template_include', function ( $modele ) {
	if ( ! get_query_var( 'sueb_gestion' ) ) {
		return $modele;
	}
	global $wp_query;
	$wp_query->is_404  = false;
	$wp_query->is_home = false;
	status_header( 200 );
	nocache_headers();
	return SUEB_DIR . '/templates/admin.php';
} );

add_filter( 'pre_get_document_title', function ( $titre ) {
	return get_query_var( 'sueb_gestion' ) ? 'Gestion du site – ' . get_bloginfo( 'name' ) : $titre;
} );

add_filter( 'wp_robots', function ( $robots ) {
	if ( get_query_var( 'sueb_gestion' ) ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
} );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! get_query_var( 'sueb_gestion' ) ) {
		return;
	}
	$v = function ( $fichier ) {
		return SUEB_VERSION . '.' . ( @filemtime( SUEB_DIR . '/assets/' . $fichier ) ?: 0 );
	};
	wp_enqueue_style( 'sueb-gestion', SUEB_URI . '/assets/css/admin.css', array( 'sueb-site' ), $v( 'css/admin.css' ) );
	/* Page autonome : ni en-tête ni pied du site, donc pas de leurs scripts. */
	wp_dequeue_script( 'sueb-site' );
	if ( ! sueb_peut_gerer() ) {
		return;
	}
	$u      = wp_get_current_user();
	$slug   = sueb_role_du_compte( $u );
	$role   = current_user_can( 'manage_options' ) ? 'Administrateur' : ( $slug ? sueb_role( $slug )['nom'] : 'Gestion' );
	$portee = sueb_portee();
	if ( 'tous' !== $portee ) {
		$role .= ' · ' . implode( ', ', array_filter( array_map( function ( $id ) {
			return (string) get_post_meta( $id, '_sueb_sigle', true );
		}, $portee ) ) );
	}
	wp_enqueue_script( 'sueb-gestion', SUEB_URI . '/assets/js/admin.js', array(), $v( 'js/admin.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_add_inline_script(
		'sueb-gestion',
		'window.SUEB_GESTION=' . wp_json_encode(
			array(
				'root'        => esc_url_raw( rest_url() ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'nom'         => $u->display_name,
				'role'        => $role,
				'logo'        => sueb_logo(),
				'site'        => home_url( '/' ),
				'deconnexion' => wp_logout_url( sueb_url_gestion() ),
				'champs'      => sueb_champs_gestion(),
				'reglages'    => sueb_reglages_gestion(),
				'niveaux'     => wp_list_pluck( sueb_niveaux(), 'un' ),
				'droits'      => sueb_permissions_de(),
				'portee'      => $portee,
				'id'          => $u->ID,
				'etablissements' => array_values( array_filter( sueb_etablissements_liste(), function ( $e ) {
					return sueb_dans_portee( $e['id'] );
				} ) ),
			),
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES
		) . ';',
		'before'
	);
}, 20 ); // après le thème (functions.php), pour pouvoir retirer site.js

/* ---------- Connexion ---------- */

add_action( 'admin_post_nopriv_sueb_connexion', 'sueb_traiter_connexion' );
add_action( 'admin_post_sueb_connexion', 'sueb_traiter_connexion' );

function sueb_traiter_connexion() {
	$retour = function ( $erreur = '' ) {
		wp_safe_redirect( $erreur ? add_query_arg( 'erreur', $erreur, sueb_url_gestion() ) : sueb_url_gestion() );
		exit;
	};
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'sueb_connexion' ) ) {
		$retour( 'session' );
	}
	/* Au plus cinq essais ratés par quart d'heure et par adresse. */
	$cle      = 'sueb_essais_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
	$essais   = (int) get_transient( $cle );
	if ( $essais >= 5 ) {
		$retour( 'trop' );
	}
	$user = wp_signon(
		array(
			'user_login'    => isset( $_POST['identifiant'] ) ? sanitize_text_field( wp_unslash( $_POST['identifiant'] ) ) : '',
			'user_password' => isset( $_POST['mdp'] ) ? wp_unslash( $_POST['mdp'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- mot de passe laissé tel quel
			'remember'      => ! empty( $_POST['memoriser'] ),
		),
		is_ssl()
	);
	if ( is_wp_error( $user ) ) {
		set_transient( $cle, $essais + 1, 15 * MINUTE_IN_SECONDS );
		$retour( 'identifiants' );
	}
	delete_transient( $cle );
	if ( ! sueb_peut_gerer( $user ) ) {
		wp_logout();
		$retour( 'droits' );
	}
	$retour();
}

/* ---------- Champs exposés à l'API REST ---------- */

/** Nettoyage d'une valeur selon le type du champ (mêmes règles que l'enregistrement dans l'admin WordPress). */
function sueb_nettoyer_champ( $v, $type ) {
	$v = is_scalar( $v ) ? (string) $v : '';
	switch ( $type ) {
		case 'media':
		case 'etablissement':
		case 'departement':
		case 'number':
			return '' === $v || ! absint( $v ) ? '' : (string) absint( $v );
		case 'url':
			return esc_url_raw( $v );
		case 'email':
			return sanitize_email( $v );
		case 'color':
			return (string) sanitize_hex_color( $v );
		case 'textarea':
			return sanitize_textarea_field( $v );
		default:
			return sanitize_text_field( $v );
	}
}

/* Toutes les métas déclarées dans sueb_champs() (inc/champs.php) sont exposées à l'API REST,
   en texte : un champ vide reste vide au lieu de devenir 0. */
add_action( 'init', function () {
	foreach ( sueb_champs() as $type => $def ) {
		/* Sans ce support, l'API REST n'expose pas le champ « meta » du type. */
		add_post_type_support( $type, 'custom-fields' );
		foreach ( $def['champs'] as $cle => $c ) {
			$genre = $c[1];
			register_post_meta( $type, '_sueb_' . $cle, array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => function ( $v ) use ( $genre ) {
					return sueb_nettoyer_champ( $v, $genre );
				},
				'auth_callback'     => function ( $permis, $cle_meta, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			) );
		}
		/* Les champs ne servent qu'à la gestion : absents des réponses publiques de l'API (courriels, etc.). */
		add_filter( 'rest_prepare_' . $type, function ( $reponse, $post, $requete ) {
			if ( 'edit' !== $requete['context'] ) {
				$donnees = $reponse->get_data();
				unset( $donnees['meta'] );
				$reponse->set_data( $donnees );
			}
			return $reponse;
		}, 10, 3 );
		/* Comme dans l'admin WordPress, un champ vidé est supprimé plutôt que stocké vide. */
		add_action( 'rest_after_insert_' . $type, function ( $post ) use ( $def ) {
			foreach ( array_keys( $def['champs'] ) as $cle ) {
				if ( '' === (string) get_post_meta( $post->ID, '_sueb_' . $cle, true ) ) {
					delete_post_meta( $post->ID, '_sueb_' . $cle );
				}
			}
		} );
	}
}, 20 );

/* ---------- Pages fixes ---------- */

/** Pages dont le thème a besoin, retrouvées par leur adresse : ni supprimées ni renommées depuis la gestion. */
function sueb_pages_theme() {
	return array(
		'actualites'         => 'Page Actualités (liste de tous les articles)',
		'histoire'           => 'Section Histoire de l’accueil',
		'message-du-recteur' => 'Message du Recteur sur l’accueil',
		'dirigeants'         => 'Page des dirigeants',
	);
}

/**
 * Vrai si le contenu ne contient que des blocs simples (ou aucun bloc) : l'éditeur de la gestion
 * peut alors le reprendre sans rien perdre, ces blocs s'affichant de même sans leurs marqueurs.
 */
function sueb_contenu_simple( $contenu ) {
	if ( ! has_blocks( $contenu ) ) {
		return true;
	}
	$simples = array( 'core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/quote', 'core/image', 'core/separator', 'core/freeform' );
	$verifier = function ( array $blocs ) use ( &$verifier, $simples ) {
		foreach ( $blocs as $b ) {
			/* Un fragment hors bloc (blockName nul) est du HTML classique : toujours repris tel quel. */
			if ( null !== $b['blockName'] && ! in_array( $b['blockName'], $simples, true ) ) {
				return false;
			}
			if ( $b['innerBlocks'] && ! $verifier( $b['innerBlocks'] ) ) {
				return false;
			}
		}
		return true;
	};
	return $verifier( parse_blocks( $contenu ) );
}

/* Ce que la gestion doit savoir d'une page : contenu modifiable ou non, rôle pour le thème. */
add_action( 'rest_api_init', function () {
	register_rest_field( 'page', 'sueb_gestion', array(
		'get_callback' => function ( $page ) {
			$post = get_post( $page['id'] );
			return array(
				'simple' => sueb_contenu_simple( $post->post_content ),
				'role'   => sueb_pages_theme()[ sueb_adresse_page_theme( $post ) ] ?? '',
			);
		},
		'schema'       => array( 'type' => 'object', 'context' => array( 'edit' ), 'readonly' => true ),
	) );
} );

/* Garde-fous côté serveur, pour les requêtes de la gestion (API REST). */
add_filter( 'rest_pre_insert_page', function ( $prepare, $requete ) {
	if ( ! $requete['id'] ) {
		return $prepare;
	}
	$actuelle = get_post( $requete['id'] );
	if ( isset( $requete['content'] ) && ! sueb_contenu_simple( $actuelle->post_content ) && ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'sueb_page_avancee', 'Cette page contient des blocs avancés : son contenu se modifie dans l’éditeur WordPress, par un administrateur.', array( 'status' => 403 ) );
	}
	$role = sueb_adresse_page_theme( $actuelle );
	if ( $role && isset( $requete['slug'] ) && $requete['slug'] !== $role ) {
		return new WP_Error( 'sueb_page_theme', 'L’adresse de cette page est utilisée par le site et ne peut pas changer.', array( 'status' => 403 ) );
	}
	return $prepare;
}, 10, 2 );

/** L'adresse d'une page dont le thème a besoin (même mise à la corbeille, « …__trashed »), sinon ''. */
function sueb_adresse_page_theme( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'page' !== $post->post_type ) {
		return '';
	}
	$adresse = preg_replace( '/__trashed(-\d+)?$/', '', $post->post_name );
	return isset( sueb_pages_theme()[ $adresse ] ) ? $adresse : '';
}

/* Une page dont le thème a besoin ne se supprime pas par l'API (corbeille comme suppression définitive). */
add_filter( 'rest_request_before_callbacks', function ( $reponse, $gestionnaire, $requete ) {
	if ( 'DELETE' === $requete->get_method() && preg_match( '#^/wp/v2/pages/(\d+)$#', $requete->get_route(), $m ) && sueb_adresse_page_theme( (int) $m[1] ) ) {
		return new WP_Error( 'sueb_page_theme', 'Cette page est utilisée par le site et ne peut pas être supprimée.', array( 'status' => 403 ) );
	}
	return $reponse;
}, 10, 3 );

/* ---------- Réglages du site (inc/personnaliser.php) : route sueb/v1/reglages ---------- */

function sueb_peut_regler() {
	return in_array( 'sueb_reglages', sueb_permissions_de(), true );
}

function sueb_peut_gerer_carte() {
	return in_array( 'sueb_partenaires', sueb_permissions_de(), true );
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'sueb/v1', '/reglages', array(
		array(
			'methods'             => 'GET',
			'permission_callback' => 'sueb_peut_regler',
			'callback'            => function () {
				$valeurs = array();
				foreach ( array_keys( sueb_reglages_tous() ) as $cle ) {
					$valeurs[ $cle ] = (string) sueb_reglage( $cle );
				}
				/* La frise non personnalisée est proposée telle qu'elle s'affiche, pour être modifiée directement. */
				if ( '' === $valeurs['frise'] ) {
					$valeurs['frise'] = sueb_frise_texte_defaut();
				}
				return $valeurs;
			},
		),
		array(
			'methods'             => 'POST',
			'permission_callback' => 'sueb_peut_regler',
			'callback'            => function ( WP_REST_Request $requete ) {
				$tous = sueb_reglages_tous();
				foreach ( (array) $requete->get_json_params() as $cle => $v ) {
					if ( ! isset( $tous[ $cle ] ) ) {
						continue;
					}
					$v = sueb_nettoyer_reglage( $v, $tous[ $cle ][1] );
					/* Inchangée, la frise par défaut n'est pas figée : elle suit encore l'arrêté. */
					if ( 'frise' === $cle && trim( $v ) === sueb_frise_texte_defaut() ) {
						$v = '';
					}
					set_theme_mod( $cle, $v );
				}
				return array( 'ok' => true );
			},
		),
	) );
} );

/* ---------- Universités et villes de la carte (inc/carte.php) : route sueb/v1/carte ---------- */

add_action( 'rest_api_init', function () {
	register_rest_route( 'sueb/v1', '/carte', array(
		array(
			'methods'             => 'GET',
			'permission_callback' => 'sueb_peut_gerer_carte',
			'callback'            => function () {
				$villes = array();
				foreach ( sueb_villes() as $nom => $c ) {
					$villes[] = array( 'nom' => $nom, 'lat' => $c[0], 'lon' => $c[1] );
				}
				return array(
					'villes'       => $villes,
					'institutions' => array_map( function ( $i ) {
						return $i + array( 'logo' => 0 );
					}, sueb_institutions_partenaires() ),
					'cadre'        => SUEB_CARTE_CADRE,
				);
			},
		),
		array(
			'methods'             => 'POST',
			'permission_callback' => 'sueb_peut_gerer_carte',
			'callback'            => function ( WP_REST_Request $requete ) {
				$resultat = sueb_enregistrer_carte( (array) $requete->get_json_params() );
				return is_wp_error( $resultat ) ? $resultat : array( 'ok' => true );
			},
		),
	) );
} );

/** Les réglages par section, au format attendu par assets/js/admin.js. */
function sueb_reglages_gestion() {
	$sortie = array();
	foreach ( sueb_reglages_def() as $id => $section ) {
		$liste = array();
		foreach ( $section['reglages'] as $cle => $r ) {
			$liste[] = array_filter( array(
				'cle'   => $cle,
				'label' => $r[0],
				'type'  => $r[1],
				'aide'  => $r[3] ?? '',
			) );
		}
		$sortie[ $id ] = $liste;
	}
	return $sortie;
}

/** Les champs complémentaires, au format attendu par assets/js/admin.js. */
function sueb_champs_gestion() {
	$sortie = array();
	foreach ( sueb_champs() as $type => $def ) {
		$liste = array();
		foreach ( $def['champs'] as $cle => $c ) {
			$f = array(
				'cle'   => $cle,
				'label' => $c[0],
				'type'  => $c[1],
			);
			if ( 'select' === $c[1] ) {
				$f['options'] = array();
				foreach ( $c[2] as $v => $l ) {
					$f['options'][] = array( (string) $v, $l );
				}
			} elseif ( ! empty( $c[2] ) ) {
				$f['aide'] = $c[2];
			}
			$liste[] = $f;
		}
		$sortie[ $type ] = array(
			'titre'  => $def['titre'],
			'aide'   => $def['aide'] ?? '',
			'champs' => $liste,
		);
	}
	return $sortie;
}