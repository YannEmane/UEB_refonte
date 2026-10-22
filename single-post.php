<?php
/**
 * La lecture d'une actualité, d'après les pages articles de MIT News et de la
 * Harvard Gazette et les règles de lisibilité (50 à 75 signes par ligne) :
 * rubrique, titre, chapeau, date et temps de lecture, partage ; la grande image
 * (ou la vidéo, l'audio) ; le texte en colonne de lecture, avec une colonne qui
 * reste visible (sommaire s'il y a des intertitres, partage) ; article précédent
 * et suivant, puis « À lire aussi ». La barre or de l'en-tête suit la lecture.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$id       = get_the_ID();
	$url      = get_permalink();
	$titre    = get_the_title();
	$rubrique = sueb_rubrique_article( $id );
	$format   = get_post_format() ?: 'article';
	$chapo    = has_excerpt() ? trim( wp_strip_all_tags( get_the_excerpt() ) ) : '';
	/* Un chapeau qui ne fait que reprendre le début du texte n'est pas répété. */
	$debut = mb_substr( trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( get_the_content() ) ) ), 0, 120 );
	if ( $chapo && 0 === strpos( preg_replace( '/\s+/u', ' ', $chapo ), mb_substr( $debut, 0, min( 120, mb_strlen( $chapo ) ) ) ) ) {
		$chapo = '';
	}
	$photo    = sueb_photo_actualite( $id, 'sueb-banniere' );
	$legende  = has_post_thumbnail() ? wp_get_attachment_caption( get_post_thumbnail_id() ) : '';
	$media    = (int) sueb_meta( $id, 'media' ) ? (string) wp_get_attachment_url( (int) sueb_meta( $id, 'media' ) ) : '';
	$integre  = str_replace( array( 'autoplay=1&', '?autoplay=1', 'autoplay=1' ), array( '', '', '' ), sueb_video_integree( (string) sueb_meta( $id, 'media_url' ) ) );
	$url_actu = sueb_url_actualites();

	/* Le texte : les intertitres reçoivent une ancre et forment le sommaire ; le lien « Source : … » devient un encadré. */
	$sommaire = array();
	$contenu  = apply_filters( 'the_content', get_the_content() );
	$contenu  = preg_replace_callback( '#<h2([^>]*)>(.*?)</h2>#is', function ( $m ) use ( &$sommaire ) {
		$texte = trim( wp_strip_all_tags( $m[2] ) );
		if ( preg_match( '/\sid="([^"]+)"/', $m[1], $i ) ) {
			$ancre = $i[1];
			$attrs = $m[1];
		} else {
			$ancre = sanitize_title( $texte ) ?: 'partie-' . ( count( $sommaire ) + 1 );
			$attrs = $m[1] . ' id="' . esc_attr( $ancre ) . '"';
		}
		$sommaire[] = array( 'ancre' => $ancre, 'texte' => $texte );
		return '<h2' . $attrs . '>' . $m[2] . '</h2>';
	}, $contenu );
	$contenu = preg_replace( '#<p>\s*(<a\s[^>]*>\s*Source\b.*?</a>)\s*</p>#isu', '<p class="article__source">' . sueb_icone( 'externe', 18 ) . '$1</p>', $contenu );

	/* À lire aussi : la même rubrique d'abord, puis les plus récentes. */
	$exclus = array( $id );
	$aussi  = $rubrique ? get_posts( array( 'post_type' => 'post', 'numberposts' => 3, 'category' => $rubrique->term_id, 'exclude' => $exclus ) ) : array();
	if ( count( $aussi ) < 3 ) {
		$aussi = array_merge( $aussi, get_posts( array( 'post_type' => 'post', 'numberposts' => 3 - count( $aussi ), 'exclude' => array_merge( $exclus, wp_list_pluck( $aussi, 'ID' ) ) ) ) );
	}
	$precedent = get_previous_post();
	$suivant   = get_next_post();
	?>
	<article class="article" aria-labelledby="article-titre">
		<header class="article__tete conteneur">
			<ol class="fil-ariane article__fil">
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li>
				<li><a href="<?php echo esc_url( $url_actu ); ?>">Actualités</a></li>
				<?php if ( $rubrique ) : ?><li><a href="<?php echo esc_url( add_query_arg( 'rubrique', $rubrique->slug, $url_actu ) ); ?>"><?php echo esc_html( $rubrique->name ); ?></a></li><?php endif; ?>
			</ol>
			<h1 class="article__titre" id="article-titre"><?php echo esc_html( $titre ); ?></h1>
			<?php if ( $chapo ) : ?><p class="article__chapo"><?php echo esc_html( $chapo ); ?></p><?php endif; ?>
			<div class="article__meta">
				<p class="article__infos">
					<span><?php echo sueb_icone( 'calendrier', 18 ); ?><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time></span>
					<span><?php echo sueb_icone( 'horloge', 18 ); ?><?php echo esc_html( sprintf( '%d min de lecture', sueb_temps_lecture( $id ) ) ); ?></span>
				</p>
				<?php echo sueb_partage( $url, $titre ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?>
			</div>
		</header>

		<figure class="article__une conteneur">
			<?php if ( 'video' === $format && ( $integre || $media ) ) : ?>
				<div class="article__video">
					<?php if ( $integre ) : ?>
						<iframe src="<?php echo esc_url( $integre ); ?>" title="<?php echo esc_attr( $titre ); ?>" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen loading="lazy"></iframe>
					<?php else : ?>
						<video src="<?php echo esc_url( $media ); ?>" poster="<?php echo esc_url( $photo ); ?>" controls preload="metadata"></video>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="article__image"><img src="<?php echo esc_url( $photo ); ?>" alt="" width="1400" height="940" fetchpriority="high" decoding="async" style="view-transition-name:actu-<?php echo (int) $id; ?>"></div>
				<?php if ( 'audio' === $format && $media ) : ?><audio class="article__audio" src="<?php echo esc_url( $media ); ?>" controls preload="metadata"></audio><?php endif; ?>
			<?php endif; ?>
			<?php if ( $legende ) : ?><figcaption><?php echo esc_html( $legende ); ?></figcaption><?php endif; ?>
		</figure>

		<div class="article__grille conteneur">
			<div class="article__corps"><?php echo $contenu; // phpcs:ignore WordPress.Security.EscapeOutput -- contenu filtré par WordPress ?></div>
			<aside class="article__rail" aria-label="Outils de lecture">
				<?php if ( count( $sommaire ) > 1 ) : ?>
					<nav class="sommaire" aria-labelledby="sommaire-titre" data-sommaire>
						<h2 class="article__rail-titre" id="sommaire-titre">Dans cet article</h2>
						<ol>
							<?php foreach ( $sommaire as $s ) : ?>
								<li><a href="#<?php echo esc_attr( $s['ancre'] ); ?>"><?php echo esc_html( $s['texte'] ); ?></a></li>
							<?php endforeach; ?>
						</ol>
					</nav>
				<?php endif; ?>
				<div class="article__rail-partage">
					<h2 class="article__rail-titre">Partager cet article</h2>
					<?php echo sueb_partage( $url, $titre ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?>
				</div>
			</aside>
		</div>

		<footer class="article__pied conteneur">
			<p class="article__classement">
				<?php if ( $rubrique ) : ?><span>Publié dans <a href="<?php echo esc_url( add_query_arg( 'rubrique', $rubrique->slug, $url_actu ) ); ?>"><?php echo esc_html( $rubrique->name ); ?></a></span><?php endif; ?>
				<a class="lien-fleche" href="<?php echo esc_url( $url_actu ); ?>">Toutes les actualités<?php echo sueb_icone( 'fleche', 18 ); ?></a>
			</p>
			<?php if ( $precedent || $suivant ) : ?>
				<nav class="voisins" aria-label="Autres actualités">
					<?php if ( $precedent ) : ?>
						<a class="voisin voisin--precedent" href="<?php echo esc_url( get_permalink( $precedent ) ); ?>">
							<span class="voisin__sens"><?php echo sueb_icone( 'gauche', 18 ); ?>Article précédent</span>
							<span class="voisin__titre"><?php echo esc_html( get_the_title( $precedent ) ); ?></span>
						</a>
					<?php endif; ?>
					<?php if ( $suivant ) : ?>
						<a class="voisin voisin--suivant" href="<?php echo esc_url( get_permalink( $suivant ) ); ?>">
							<span class="voisin__sens">Article suivant<?php echo sueb_icone( 'droite', 18 ); ?></span>
							<span class="voisin__titre"><?php echo esc_html( get_the_title( $suivant ) ); ?></span>
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		</footer>
	</article>

	<?php if ( $aussi ) : ?>
		<section class="a-lire" aria-labelledby="a-lire-titre">
			<div class="conteneur">
				<div class="a-lire__tete">
					<h2 id="a-lire-titre">À lire aussi</h2>
					<a class="lien-fleche" href="<?php echo esc_url( $url_actu ); ?>">Toutes les actualités<?php echo sueb_icone( 'fleche', 18 ); ?></a>
				</div>
				<ul class="cartes-actu" data-cascade="actus">
					<?php foreach ( $aussi as $a ) : ?>
						<?php echo sueb_carte_actualite( $a ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<script type="application/ld+json"><?php
	echo wp_json_encode( array(
		'@context'      => 'https://schema.org',
		'@type'         => 'NewsArticle',
		'headline'      => $titre,
		'description'   => $chapo,
		'image'         => array( $photo ),
		'datePublished' => get_the_date( 'c' ),
		'dateModified'  => get_the_modified_date( 'c' ),
		'mainEntityOfPage' => $url,
		'publisher'     => array( '@type' => 'CollegeOrUniversity', 'name' => SUEB_UNIVERSITE['fr'], 'logo' => array( '@type' => 'ImageObject', 'url' => sueb_logo() ) ),
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG );
	?></script>
	<?php
endwhile;

get_footer();
