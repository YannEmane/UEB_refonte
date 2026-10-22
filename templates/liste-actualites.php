<?php
/**
 * Toutes les actualités : une grille de cartes (photo, date, rubrique, titre,
 * résumé), douze par page, avec pagination.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

get_header();
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
			<?php while ( have_posts() ) : the_post(); echo sueb_carte_article( get_the_ID() ); endwhile; // phpcs:ignore ?>
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
