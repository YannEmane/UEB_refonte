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

/* ---------- Rôle et droits ---------- */

/** Droits du gestionnaire : tout le contenu et les médias, sans la technique (extensions, thèmes, utilisateurs). */
function sueb_droits_gestionnaire() {
	$droits = array( 'read', 'upload_files', 'manage_categories' );
	foreach ( array( 'posts', 'pages' ) as $t ) {
		foreach ( array( 'edit_', 'edit_others_', 'edit_published_', 'edit_private_', 'publish_', 'delete_', 'delete_others_', 'delete_published_', 'delete_private_', 'read_private_' ) as $a ) {
			$droits[] = $a . $t;
		}
	}
	return array_fill_keys( $droits, true );
}

add_action( 'init', function () {
	if ( ! get_role( 'gestionnaire_ueb' ) ) {
		add_role( 'gestionnaire_ueb', 'Gestionnaire UEb', sueb_droits_gestionnaire() );
	}
} );

/** Qui peut ouvrir la page de gestion : administrateurs, éditeurs et gestionnaires UEb. */
function sueb_peut_gerer() {
	return is_user_logged_in() && current_user_can( 'edit_others_posts' );
}

function sueb_url_gestion() {
	return home_url( '/gestion/' );
}

/** Le gestionnaire travaille sur le site : pas de barre d'administration, ni d'accès à l'admin WordPress. */
function sueb_est_gestionnaire_seul() {
	$u = wp_get_current_user();
	return $u->exists() && in_array( 'gestionnaire_ueb', (array) $u->roles, true ) && ! current_user_can( 'manage_options' );
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
	if ( ! sueb_peut_gerer() ) {
		return;
	}
	wp_enqueue_script( 'sueb-gestion', SUEB_URI . '/assets/js/admin.js', array(), $v( 'js/admin.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_add_inline_script(
		'sueb-gestion',
		'window.SUEB_GESTION=' . wp_json_encode(
			array(
				'root'        => esc_url_raw( rest_url() ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'nom'         => wp_get_current_user()->display_name,
				'deconnexion' => wp_logout_url( sueb_url_gestion() ),
			),
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES
		) . ';',
		'before'
	);
} );

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
	if ( ! user_can( $user, 'edit_others_posts' ) ) {
		wp_logout();
		$retour( 'droits' );
	}
	$retour();
}

/* ---------- Champs exposés à l'API REST ---------- */

add_action( 'init', function () {
	$champs = array(
		'_sueb_media'     => array( 'integer', 'absint' ),
		'_sueb_media_url' => array( 'string', 'esc_url_raw' ),
		'_sueb_duree'     => array( 'string', 'sanitize_text_field' ),
	);
	foreach ( $champs as $cle => $def ) {
		register_post_meta( 'post', $cle, array(
			'type'              => $def[0],
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => $def[1],
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		) );
	}
} );