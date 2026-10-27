<?php
/**
 * Personnel de l'UEb sur l'accueil : le Recteur, le PCA et les vice-recteurs,
 * en un seul tableau à deux tons (le Conseil d'administration et le Rectorat sur
 * le vert, le Vice-Rectorat dessous, sur le blanc). Le reste de la direction et
 * tout le personnel sont dans l'annuaire (/personnel/, archive-ueb_personnel.php
 * et templates/annuaire.php), dont le lien est dans l'en-tête.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$direction = array();
foreach ( sueb_personnel_par_corps() as $g ) {
	if ( 'direction' === $g['slug'] ) {
		$direction = $g['membres'];
	}
}
$poles  = sueb_direction_par_pole( $direction );
$etages = array_filter( array(
	'tete' => $poles['rectorat'] ?? null,
	'vr'   => $poles['vice-rectorat'] ?? null,
) );
?>
<section class="section personnel personnel--accueil" id="personnel" aria-labelledby="titre-perso">
	<div class="conteneur">
		<header class="section__tete section__tete--ligne revele">
			<div>
				<p class="surtitre">Personnels de l’UEb</p>
				<h2 id="titre-perso">Celles et ceux qui font l’université</h2>
				<p class="section__chapo">Le Recteur, le Président du Conseil d’administration et les vice-recteurs. Le secrétariat général, les directions centrales, les doyens et l’ensemble du personnel sont présentés dans l’annuaire.</p>
			</div>
			<a class="btn btn--plein" href="<?php echo esc_url( get_post_type_archive_link( 'ueb_personnel' ) ); ?>">Voir tout le personnel<?php echo sueb_icone( 'fleche', 18 ); ?></a>
		</header>

		<?php if ( $etages ) : ?>
			<div class="rectorat" data-cascade="rectorat">
				<?php $i = 0; foreach ( $etages as $cle => $pole ) : ?>
					<section class="rectorat__<?php echo esc_attr( $cle ); ?>" style="--i:<?php echo (int) $i; ?>" aria-labelledby="rectorat-<?php echo esc_attr( $cle ); ?>">
						<h3 class="sr" id="rectorat-<?php echo esc_attr( $cle ); ?>"><?php echo esc_html( $pole['nom'] ); ?></h3>
						<ul class="rectorat__liste">
							<?php foreach ( $pole['membres'] as $j => $m ) : ?>
								<li class="dirigeant" style="--j:<?php echo (int) $j; ?>">
									<a class="dirigeant__lien" href="<?php echo esc_url( $m['url'] ); ?>" data-cv-lien>
										<span class="dirigeant__portrait"><?php echo sueb_portrait_personnel( $m ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?></span>
										<span class="dirigeant__texte">
											<strong class="dirigeant__nom"><?php echo esc_html( $m['nom'] ); ?></strong>
											<span class="dirigeant__fonction"><?php echo esc_html( $m['fonction'] ); ?></span>
										</span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php ++$i; endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
