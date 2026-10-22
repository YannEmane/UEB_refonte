<?php
/**
 * Lecture d'une actualité : en-tête (rubrique, date, temps de lecture), image à la une,
 * média (vidéo ou audio), texte, partage, article précédent/suivant, puis « Découvrir aussi ».
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();

$id       = get_the_ID();
$format   = get_post_format();
$cats     = get_the_category();
$rubrique = $cats && ! in_array( $cats[0]->slug, array( 'uncategorized', 'non-classe' ), true ) ? $cats[0] : null;
$mots     = count( preg_split( '/\s+/u', trim( wp_strip_all_tags( get_the_content() ) ), -1, PREG_SPLIT_NO_EMPTY ) );
$minutes  = max( 1, (int) ceil( $mots / 200 ) );
$photo    = sueb_image_url( $id, 'sueb-banniere' ) ?: sueb_repli_article();
$media    = (int) sueb_meta( $id, 'media' );
$media_u  = $media ? (string) wp_get_attachment_url( $media ) : '';
$integre  = remove_query_arg( 'autoplay', sueb_video_integree( (string) sueb_meta( $id, 'media_url' ) ) );
$duree    = (string) sueb_meta( $id, 'duree' );
$source   = (string) sueb_meta( $id, 'lien' );
$url      = get_permalink();
$titre    = get_the_title();
$precedent = get_adjacent_post( false, '', true );
$suivant   = get_adjacent_post( false, '', false );
$autres    = sueb_articles_a_decouvrir( $id, 3 );
?>
<header class="article-tete">
	<div class="conteneur article-tete__int">
		<ol class="fil-ariane">
			<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li>
			<li><a href="<?php echo esc_url( sueb_url_actualites() ); ?>">Actualités</a></li>
		</ol>
		<p class="article-tete__etiquettes">
			<?php if ( $rubrique ) : ?><span class="surtitre surtitre--clair"><?php echo esc_html( $rubrique->name ); ?></span><?php endif; ?>
			<?php if ( 'video' === $format ) : ?><span class="article-tete__format article-tete__format--video"><?php echo sueb_icone( 'video', 16 ); ?>Vidéo</span><?php elseif ( 'audio' === $format ) : ?><span class="article-tete__format article-tete__format--audio"><?php echo sueb_icone( 'audio', 16 ); ?>Audio</span><?php endif; ?>
		</p>
		<h1><?php echo esc_html( $titre ); ?></h1>
		<p class="article-tete__meta">
			<span><?php echo sueb_icone( 'calendrier', 16 ); ?><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time></span>
			<span><?php echo sueb_icone( 'livre', 16 ); ?><?php echo esc_html( $minutes ); ?> min de lecture</span>
			<?php if ( $duree && $format ) : ?><span><?php echo sueb_icone( 'lecture', 14 ); ?><?php echo esc_html( $duree ); ?></span><?php endif; ?>
		</p>
	</div>
</header>

<div class="article-page">
	<div class="conteneur">
		<?php if ( 'video' === $format && ( $media_u || $integre ) ) : ?>
			<figure class="article-visuel article-visuel--media">
				<?php if ( $integre ) : ?>
					<iframe src="<?php echo esc_url( $integre ); ?>" title="<?php echo esc_attr( $titre ); ?>" loading="lazy" allow="accelerometer; encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe>
				<?php else : ?>
					<video src="<?php echo esc_url( $media_u ); ?>" controls preload="metadata" poster="<?php echo esc_url( $photo ); ?>"></video>
				<?php endif; ?>
			</figure>
		<?php else : ?>
			<figure class="article-visuel">
				<img src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( $titre ); ?>" width="1400" height="940" decoding="async" fetchpriority="high">
			</figure>
		<?php endif; ?>

		<div class="article-grille">
			<aside class="article-partage" aria-label="Partager cet article">
				<span class="article-partage__titre">Partager</span>
				<a href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( $titre . ' ' . $url ) ); ?>" target="_blank" rel="noopener" aria-label="Partager sur WhatsApp"><?php echo sueb_icone( 'whatsapp', 20 ); ?></a>
				<a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ) ); ?>" target="_blank" rel="noopener" aria-label="Partager sur Facebook"><?php echo sueb_icone( 'facebook', 20 ); ?></a>
				<a href="<?php echo esc_url( 'mailto:?subject=' . rawurlencode( $titre ) . '&body=' . rawurlencode( $url ) ); ?>" aria-label="Envoyer par courriel"><?php echo sueb_icone( 'courriel', 20 ); ?></a>
			</aside>

			<article class="article-corps">
				<?php if ( 'audio' === $format && $media_u ) : ?>
					<div class="article-audio"><?php echo sueb_icone( 'audio', 22 ); ?><audio controls preload="none" src="<?php echo esc_url( $media_u ); ?>"></audio></div>
				<?php endif; ?>
				<div class="prose article-texte"><?php the_content(); ?></div>

				<?php if ( $source ) : ?>
					<p class="article-source"><a class="btn btn--contour" href="<?php echo esc_url( $source ); ?>" target="_blank" rel="noopener">Lire l’article de presse<?php echo sueb_icone( 'externe', 18 ); ?></a></p>
				<?php endif; ?>

				<?php if ( $precedent || $suivant ) : ?>
					<nav class="article-nav" aria-label="Autres actualités">
						<?php if ( $precedent ) : ?>
							<a class="article-nav__lien article-nav__lien--prec" href="<?php echo esc_url( get_permalink( $precedent ) ); ?>"><small><?php echo sueb_icone( 'gauche', 16 ); ?>Article précédent</small><strong><?php echo esc_html( get_the_title( $precedent ) ); ?></strong></a>
						<?php endif; ?>
						<?php if ( $suivant ) : ?>
							<a class="article-nav__lien article-nav__lien--suiv" href="<?php echo esc_url( get_permalink( $suivant ) ); ?>"><small>Article suivant<?php echo sueb_icone( 'droite', 16 ); ?></small><strong><?php echo esc_html( get_the_title( $suivant ) ); ?></strong></a>
						<?php endif; ?>
					</nav>
				<?php endif; ?>
			</article>
		</div>
	</div>
</div>

<?php if ( $autres ) : ?>
	<section class="section decouvrir-aussi" aria-labelledby="titre-aussi">
		<div class="conteneur">
			<header class="section__tete section__tete--ligne">
				<div>
					<p class="surtitre">Découvrir aussi</p>
					<h2 id="titre-aussi">À lire également</h2>
				</div>
				<a class="btn btn--plein" href="<?php echo esc_url( sueb_url_actualites() ); ?>">Voir tous les articles<?php echo sueb_icone( 'fleche', 18 ); ?></a>
			</header>
			<ul class="liste-actus">
				<?php foreach ( $autres as $autre ) { echo sueb_carte_article( $autre ); } // phpcs:ignore ?>
			</ul>
		</div>
	</section>
<?php endif; ?>
<?php
get_footer();
