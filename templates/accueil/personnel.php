<?php
/**
 * Personnel de l'UEb sur l'accueil : la direction de l'université seule, en
 * grands portraits, puis un lien vers l'annuaire complet (/personnel/,
 * archive-ueb_personnel.php et templates/annuaire.php).
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
?>
<section class="section personnel" id="personnel" aria-labelledby="titre-perso">
	<div class="conteneur">
		<header class="section__tete revele">
			<p class="surtitre">Personnels de l’UEb</p>
			<h2 id="titre-perso">Celles et ceux qui font l’université</h2>
			<p class="section__chapo">La direction de l’université. Les doyens, directeurs et l’ensemble du personnel sont présentés dans l’annuaire.</p>
		</header>

		<?php if ( $direction ) : ?>
			<ul class="profils profils--direction">
				<?php foreach ( $direction as $m ) : ?>
					<?php echo sueb_carte_personnel( $m ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<p class="personnel__suite">
			<a class="btn btn--plein" href="<?php echo esc_url( get_post_type_archive_link( 'ueb_personnel' ) ); ?>">Voir tout le personnel<?php echo sueb_icone( 'fleche', 18 ); ?></a>
		</p>
	</div>
</section>
