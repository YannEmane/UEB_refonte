<?php
/**
 * Gabarit par défaut : pages, actualités, partenaires, personnel, listes.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="page-tete">
	<div class="conteneur">
		<ol class="fil-ariane"><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li><?php if ( is_singular() && 'page' !== get_post_type() ) : $type = get_post_type_object( get_post_type() ); ?><li><a href="<?php echo esc_url( get_post_type_archive_link( get_post_type() ) ?: home_url( '/' ) ); ?>"><?php echo esc_html( 'post' === get_post_type() ? 'Actualités' : $type->labels->name ); ?></a></li><?php endif; ?></ol>
		<h1><?php echo esc_html( is_singular() ? get_the_title() : wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
	</div>
</header>
<div class="conteneur page-corps">
	<?php if ( is_singular() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article class="prose">
				<?php if ( has_post_thumbnail() && 'ueb_personnel' !== get_post_type() ) : ?><?php the_post_thumbnail( 'large' ); ?><?php elseif ( 'post' === get_post_type() ) : ?><img class="attachment-large" src="<?php echo esc_url( sueb_photo( sueb_repli_article() ) ); ?>" alt="" width="1600" height="900" loading="lazy"><?php endif; ?>
				<?php the_content(); ?>
				<?php if ( ( $lien = sueb_meta( get_the_ID(), 'lien' ) ) ) : ?>
					<p><a class="lien-fleche" href="<?php echo esc_url( $lien ); ?>" target="_blank" rel="noopener">Lire l’article de presse</a></p>
				<?php endif; ?>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<ul class="grille-enfants">
			<?php while ( have_posts() ) : the_post(); ?>
				<li><a href="<?php the_permalink(); ?>"><strong><?php the_title(); ?></strong><small><?php echo esc_html( get_the_date() ); ?></small></a></li>
			<?php endwhile; ?>
		</ul>
		<?php the_posts_pagination(); ?>
	<?php endif; ?>
</div>
<?php
get_footer();