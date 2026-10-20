<?php
/**
 * Page « Toutes les actualités » (/actualites/) : la liste paginée de tous
 * les articles publiés. L'adresse ne dépend d'aucun réglage de WordPress
 * (pas besoin de page « Articles » dans Réglages › Lecture).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/** Adresse de la liste de toutes les actualités. */
function sueb_url_actualites() {
	return home_url( '/actualites/' );
}

add_action( 'init', function () {
	add_rewrite_rule( '^actualites/?$', 'index.php?sueb_actus=1', 'top' );
	add_rewrite_rule( '^actualites/page/([0-9]+)/?$', 'index.php?sueb_actus=1&paged=$matches[1]', 'top' );
} );

/* Les règles d'adresses s'enregistrent une seule fois (inutile d'aller dans Réglages › Permaliens). */
add_action( 'init', function () {
	if ( '1' !== get_option( 'sueb_regles_actus' ) ) {
		flush_rewrite_rules( false );
		update_option( 'sueb_regles_actus', '1' );
	}
}, 99 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'sueb_actus';
	return $vars;
} );

/* La requête : tous les articles, les plus récents d'abord, douze par page. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() || ! $q->get( 'sueb_actus' ) ) {
		return;
	}
	$q->set( 'post_type', 'post' );
	$q->set( 'post_status', 'publish' );
	$q->set( 'posts_per_page', 12 );
	$q->set( 'ignore_sticky_posts', true );
	/* Ce n'est ni l'accueil ni la page des articles de WordPress. */
	$q->is_home    = false;
	$q->is_archive = true;
} );

add_filter( 'template_include', function ( $modele ) {
	if ( get_query_var( 'sueb_actus' ) ) {
		$fichier = SUEB_DIR . '/templates/liste-actualites.php';
		if ( file_exists( $fichier ) ) {
			status_header( 200 );
			return $fichier;
		}
	}
	return $modele;
} );

add_filter( 'document_title_parts', function ( $parts ) {
	if ( get_query_var( 'sueb_actus' ) ) {
		$parts['title'] = 'Toutes les actualités';
	}
	return $parts;
} );

/* Sans cette précaution, WordPress pourrait « corriger » l'adresse vers l'accueil. */
add_filter( 'redirect_canonical', function ( $url ) {
	return get_query_var( 'sueb_actus' ) ? false : $url;
} );

/**
 * Les articles proposés sous un article : d'abord ceux de la même rubrique,
 * puis les plus récents, sans jamais reprendre l'article lu.
 */
function sueb_articles_a_decouvrir( $post_id, $nombre = 3 ) {
	$ids  = array();
	$cats = wp_get_post_categories( $post_id );
	if ( $cats ) {
		$ids = get_posts( array( 'post_type' => 'post', 'category__in' => $cats, 'post__not_in' => array( $post_id ), 'numberposts' => $nombre, 'fields' => 'ids', 'ignore_sticky_posts' => true ) );
	}
	if ( count( $ids ) < $nombre ) {
		$ids = array_merge( $ids, get_posts( array( 'post_type' => 'post', 'post__not_in' => array_merge( array( $post_id ), $ids ), 'numberposts' => $nombre - count( $ids ), 'fields' => 'ids', 'ignore_sticky_posts' => true ) ) );
	}
	return $ids;
}

/** Une carte d'article (liste des actualités et « Découvrir aussi »). */
function sueb_carte_article( $id ) {
	$format = get_post_format( $id );
	$cats   = get_the_category( $id );
	$rubr   = $cats && ! in_array( $cats[0]->slug, array( 'uncategorized', 'non-classe' ), true ) ? $cats[0]->name : '';
	$photo  = sueb_image_url( $id, 'sueb-carte' ) ?: sueb_repli_article();
	$etiq   = array( 'video' => 'Vidéo', 'audio' => 'Audio' );
	ob_start();
	?>
	<li class="liste-actus__item">
		<a class="liste-actus__lien" href="<?php echo esc_url( get_permalink( $id ) ); ?>">
			<span class="liste-actus__photo">
				<img src="<?php echo esc_url( $photo ); ?>" alt="" loading="lazy" decoding="async">
				<?php if ( isset( $etiq[ $format ] ) ) : ?><span class="badge-format badge-format--<?php echo esc_attr( $format ); ?>"><?php echo sueb_icone( $format, 14 ); ?><?php echo esc_html( $etiq[ $format ] ); ?></span><?php endif; ?>
			</span>
			<span class="liste-actus__corps">
				<span class="liste-actus__meta"><time datetime="<?php echo esc_attr( get_the_date( 'c', $id ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y', $id ) ); ?></time><?php echo $rubr ? '<span>' . esc_html( $rubr ) . '</span>' : ''; ?></span>
				<strong class="liste-actus__titre"><?php echo esc_html( get_the_title( $id ) ); ?></strong>
				<span class="liste-actus__resume"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $id ) ), 28 ) ); ?></span>
				<span class="lien-fleche">Lire la suite<?php echo sueb_icone( 'fleche', 18 ); ?></span>
			</span>
		</a>
	</li>
	<?php
	return ob_get_clean();
}
