<?php
/**
 * La page Actualités (/actualites/, page WordPress « actualites ») : toutes les
 * nouvelles de l'université, à la manière de MIT News et de la Harvard Gazette.
 * La dernière actualité est à la une, sur le vert de l'en-tête ; les autres
 * suivent en grille (sueb_carte_actualite), avec recherche (?q=), rubriques
 * (?rubrique=) et pagination. Un clic ouvre l'article (single-post.php) ; la
 * photo de la carte y devient l'image de l'article (transition de page).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$base     = sueb_url_actualites();
$cherche  = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- simple recherche
$rubrique = isset( $_GET['rubrique'] ) ? sanitize_title( wp_unslash( $_GET['rubrique'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- simple filtre
$filtre   = '' !== $cherche || '' !== $rubrique;
$numero   = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );

/* Sans filtre, la première page montre la une et neuf cartes, les suivantes neuf cartes. */
$premiere = 10;
$par_page = 9;
$args     = array( 'post_type' => 'post', 'post_status' => 'publish', 'ignore_sticky_posts' => true, 's' => $cherche, 'category_name' => $rubrique );
if ( $filtre ) {
	$args += array( 'posts_per_page' => $par_page, 'paged' => $numero );
} else {
	$args += array( 'posts_per_page' => 1 === $numero ? $premiere : $par_page, 'offset' => 1 === $numero ? 0 : $premiere + ( $numero - 2 ) * $par_page );
}
$requete  = new WP_Query( $args );
$total    = (int) $requete->found_posts;
$nb_pages = $filtre ? (int) $requete->max_num_pages : ( $total <= $premiere ? 1 : 1 + (int) ceil( ( $total - $premiere ) / $par_page ) );
/* Une page au-delà de la dernière renvoie à la première. */
if ( $numero > max( 1, $nb_pages ) ) {
	wp_safe_redirect( add_query_arg( array_filter( array( 'q' => $cherche, 'rubrique' => $rubrique ) ), $base ) );
	exit;
}
$articles = $requete->posts;
$une      = ! $filtre && 1 === $numero && $articles ? array_shift( $articles ) : null;

/* Les rubriques ne s'affichent qu'à partir de deux. */
$rubriques = array_values( array_filter( get_categories( array( 'hide_empty' => true ) ), function ( $c ) {
	return ! in_array( $c->slug, array( 'uncategorized', 'non-classe' ), true );
} ) );
$nom_rubrique = '';
foreach ( $rubriques as $c ) {
	if ( $c->slug === $rubrique ) {
		$nom_rubrique = $c->name;
	}
}

if ( '' !== $cherche ) {
	$titre_liste = sprintf( 'Résultats pour « %s »', $cherche );
} elseif ( $nom_rubrique ) {
	$titre_liste = $nom_rubrique;
} else {
	$titre_liste = $numero > 1 ? sprintf( 'Actualités, page %d', $numero ) : 'Toutes les actualités';
}

get_header();
?>
<header class="actus-tete">
	<div class="conteneur">
		<ol class="fil-ariane"><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li><li aria-current="page">Actualités</li></ol>
		<div class="actus-tete__ligne">
			<div>
				<h1 class="actus-tete__titre">Actualités</h1>
				<p class="actus-tete__chapo">Conventions, recherche, vie des établissements : les nouvelles de l’Université d’Ebolowa.</p>
			</div>
			<form class="actus-recherche" role="search" action="<?php echo esc_url( $base ); ?>" method="get">
				<label class="sr" for="actus-q">Rechercher une actualité</label>
				<?php echo sueb_icone( 'recherche', 20 ); ?>
				<input id="actus-q" type="search" name="q" value="<?php echo esc_attr( $cherche ); ?>" placeholder="Rechercher une actualité" autocomplete="off">
				<?php if ( $rubrique ) : ?><input type="hidden" name="rubrique" value="<?php echo esc_attr( $rubrique ); ?>"><?php endif; ?>
				<button class="actus-recherche__envoi" type="submit">Rechercher</button>
			</form>
		</div>

		<?php if ( $une ) : $r = sueb_rubrique_article( $une ); ?>
			<article class="actu-une" aria-labelledby="actu-une-titre">
				<a class="actu-une__image" href="<?php echo esc_url( get_permalink( $une ) ); ?>" tabindex="-1" aria-hidden="true">
					<img src="<?php echo esc_url( sueb_photo_actualite( $une->ID, 'sueb-banniere' ) ); ?>" alt="" width="1400" height="940" fetchpriority="high" decoding="async" data-vt="actu-<?php echo (int) $une->ID; ?>">
				</a>
				<div class="actu-une__texte">
					<p class="actu-une__meta">
						<span class="actu-une__etiquette">À la une</span>
						<?php if ( $r ) : ?><span><?php echo esc_html( $r->name ); ?></span><?php endif; ?>
						<time datetime="<?php echo esc_attr( get_the_date( 'c', $une ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y', $une ) ); ?></time>
					</p>
					<h2 class="actu-une__titre" id="actu-une-titre"><a href="<?php echo esc_url( get_permalink( $une ) ); ?>"><?php echo esc_html( get_the_title( $une ) ); ?></a></h2>
					<p class="actu-une__resume"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $une ) ), 40 ) ); ?></p>
					<p class="actu-une__pied">
						<a class="btn btn--or" href="<?php echo esc_url( get_permalink( $une ) ); ?>">Lire l’article<?php echo sueb_icone( 'fleche', 18 ); ?></a>
						<span class="actu-une__temps"><?php echo sueb_icone( 'horloge', 16 ); ?><?php echo esc_html( sprintf( '%d min de lecture', sueb_temps_lecture( $une ) ) ); ?></span>
					</p>
				</div>
			</article>
		<?php endif; ?>
	</div>
</header>

<section class="actus-liste" aria-labelledby="actus-liste-titre">
	<div class="conteneur">
		<div class="actus-liste__tete">
			<h2 class="actus-liste__titre" id="actus-liste-titre"><?php echo esc_html( $titre_liste ); ?></h2>
			<p class="actus-liste__compte"><?php echo esc_html( sprintf( _n( '%d actualité', '%d actualités', $total ), $total ) ); ?><?php if ( $filtre ) : ?><a href="<?php echo esc_url( $base ); ?>">Effacer</a><?php endif; ?></p>
		</div>

		<?php if ( count( $rubriques ) > 1 ) : ?>
			<nav class="filtres actus-rubriques" aria-label="Rubriques">
				<a href="<?php echo esc_url( $cherche ? add_query_arg( 'q', $cherche, $base ) : $base ); ?>"<?php echo $rubrique ? '' : ' aria-current="page"'; ?>>Toutes</a>
				<?php foreach ( $rubriques as $c ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array_filter( array( 'rubrique' => $c->slug, 'q' => $cherche ) ), $base ) ); ?>"<?php echo $c->slug === $rubrique ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ( $articles ) : ?>
			<ul class="cartes-actu" data-cascade="actus">
				<?php foreach ( $articles as $a ) : ?>
					<?php echo sueb_carte_actualite( $a ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?>
				<?php endforeach; ?>
			</ul>
		<?php elseif ( $filtre ) : ?>
			<div class="actus-vide">
				<p><strong>Aucune actualité ne correspond à cette recherche.</strong> Vérifiez l’orthographe, ou essayez un autre mot.</p>
				<a class="btn btn--plein" href="<?php echo esc_url( $base ); ?>">Voir toutes les actualités</a>
			</div>
		<?php elseif ( ! $une ) : ?>
			<p class="vide">Les premières actualités arrivent bientôt.</p>
		<?php endif; ?>

		<?php if ( $nb_pages > 1 ) : ?>
			<nav class="pagination-actu" aria-label="Pages des actualités">
				<?php
				echo paginate_links( array( // phpcs:ignore WordPress.Security.EscapeOutput -- produit par WordPress
					'base'      => trailingslashit( $base ) . 'page/%#%/',
					'format'    => '',
					'current'   => $numero,
					'total'     => $nb_pages,
					'add_args'  => array_filter( array( 'q' => $cherche, 'rubrique' => $rubrique ) ),
					'prev_text' => sueb_icone( 'gauche', 18 ) . '<span>Précédente</span>',
					'next_text' => '<span>Suivante</span>' . sueb_icone( 'droite', 18 ),
				) );
				?>
			</nav>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
