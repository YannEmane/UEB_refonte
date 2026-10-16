<?php
/**
 * Toutes les actualités : une grille de cartes (photo, date, rubrique, titre,
 * résumé), douze par page, avec pagination.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

get_header();
$libelles = array( 'video' => 'Vidéo', 'audio' => 'Audio' );
?>
<header class="page-tete">
	<div class="conteneur">
		<ol class="fil-ariane"><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li><li><a href="<?php echo esc_url( home_url( '/#actualites' ) ); ?>">Nouvelles et évènements</a></li></ol>
		<p class="surtitre surtitre--clair">Nouvelles et évènements</p>
		<h1>Toutes les actualités</h1>
	</div>
</header>
<div class="conteneur page-corps">
	<?php if ( ! have_posts() ) : ?>
		<p class="vide">Aucune actualité n’est publiée pour le moment.</p>
	<?php else : ?>
		<ul class="liste-actus">
			<?php
			while ( have_posts() ) :
				the_post();
				$format = get_post_format();
				$cats   = get_the_category();
				$rubr   = $cats && ! in_array( $cats[0]->slug, array( 'uncategorized', 'non-classe' ), true ) ? $cats[0]->name : '';
				$photo  = sueb_image_url( get_the_ID(), 'sueb-carte' ) ?: sueb_repli_article();
				?>
				<li class="liste-actus__item">
					<a class="liste-actus__lien" href="<?php the_permalink(); ?>">
						<span class="liste-actus__photo">
							<img src="<?php echo esc_url( $photo ); ?>" alt="" loading="lazy" decoding="async">
							<?php if ( isset( $libelles[ $format ] ) ) : ?><span class="badge-format badge-format--<?php echo esc_attr( $format ); ?>"><?php echo sueb_icone( $format, 14 ); ?><?php echo esc_html( $libelles[ $format ] ); ?></span><?php endif; ?>
						</span>
						<span class="liste-actus__corps">
							<span class="liste-actus__meta"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time><?php echo $rubr ? '<span>' . esc_html( $rubr ) . '</span>' : ''; ?></span>
							<strong class="liste-actus__titre"><?php the_title(); ?></strong>
							<span class="liste-actus__resume"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 28 ) ); ?></span>
							<span class="lien-fleche">Lire la suite<?php echo sueb_icone( 'fleche', 18 ); ?></span>
						</span>
					</a>
				</li>
			<?php endwhile; ?>
		</ul>
		<?php
		the_posts_pagination( array(
			'mid_size'  => 1,
			'prev_text' => 'Précédent',
			'next_text' => 'Suivant',
		) );
		?>
	<?php endif; ?>
</div>
<?php
get_footer();
