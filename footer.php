<?php
/**
 * Pied de page : coordonnées, rubriques, établissements.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$u    = SUEB_UNIVERSITE;
$base = is_front_page() ? '' : home_url( '/' );
?>
</main>

<footer class="pied" id="contact">
	<div class="pied__canopee" aria-hidden="true"></div>
	<div class="conteneur pied__grille">
		<div class="pied__marque">
			<img src="<?php echo esc_url( sueb_logo() ); ?>" alt="" width="88" height="84" loading="lazy">
			<p class="pied__nom">Université d’Ebolowa<span>The University of Ebolowa</span></p>
			<ul class="pied__contacts">
				<li><?php echo sueb_icone( 'lieu', 18 ); ?><span><?php echo esc_html( $u['bp'] ); ?><br><?php echo esc_html( $u['ville'] ); ?></span></li>
				<li><?php echo sueb_icone( 'telephone', 18 ); ?><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $u['tel'] ) ); ?>"><?php echo esc_html( $u['tel'] ); ?></a></li>
				<li><?php echo sueb_icone( 'courriel', 18 ); ?><a href="mailto:<?php echo esc_attr( $u['email'] ); ?>"><?php echo esc_html( $u['email'] ); ?></a></li>
			</ul>
		</div>
		<div>
			<h2 class="pied__titre">L’université</h2>
			<ul class="pied__liens">
				<li><a href="<?php echo esc_url( $base . '#message' ); ?>">Message du Recteur</a></li>
				<li><a href="<?php echo esc_url( $base . '#histoire' ); ?>">Histoire et création</a></li>
				<li><a href="<?php echo esc_url( sueb_url_dirigeants() ); ?>">Les dirigeants</a></li>
				<li><a href="<?php echo esc_url( $base . '#partenariats' ); ?>">Partenariats et conventions</a></li>
				<li><a href="<?php echo esc_url( $base . '#personnel' ); ?>">Personnel</a></li>
			</ul>
		</div>
		<div>
			<h2 class="pied__titre">Établissements</h2>
			<ul class="pied__liens pied__liens--colonnes">
				<?php foreach ( sueb_etablissements() as $sigle => $e ) : ?>
					<li><a href="<?php echo esc_url( $base . '#etab-' . strtolower( $sigle ) ); ?>" title="<?php echo esc_attr( $e['fr'] ); ?>"><?php echo esc_html( $sigle ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div>
			<h2 class="pied__titre">Étudier à l’UEb</h2>
			<ul class="pied__liens">
				<li><a href="<?php echo esc_url( SUEB_LIENS['preinscription'] ); ?>">Préinscription en ligne</a></li>
				<li><a href="<?php echo esc_url( SUEB_LIENS['inscription'] ); ?>">Inscription administrative</a></li>
				<li><a href="<?php echo esc_url( $base . '#actualites' ); ?>">Actualités</a></li>
				<li><a href="<?php echo esc_url( $base . '#agenda' ); ?>">Agenda</a></li>
			</ul>
		</div>
	</div>
	<div class="conteneur pied__bas">
		<p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> Université d’Ebolowa. Tous droits réservés.</p>
		<p>Créée par le décret présidentiel n° 2022/009 du 6 janvier 2022.</p>
	</div>
</footer>

<button class="haut-de-page" type="button" data-haut aria-label="Revenir en haut de la page"><?php echo sueb_icone( 'chevron', 22 ); ?></button>

<?php wp_footer(); ?>
</body>
</html>