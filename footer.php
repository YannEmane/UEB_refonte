<?php
/**
 * Pied de page : un appel à s'inscrire, l'identité et les réseaux de l'UEb,
 * les rubriques et les coordonnées, puis les neuf établissements.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$u    = SUEB_UNIVERSITE;
$base = is_front_page() ? '' : home_url( '/' );
?>
</main>

<footer class="pied" id="contact">
	<div class="conteneur">
		<div class="pied__appel revele">
			<p class="pied__accroche">Votre parcours commence à Ebolowa.</p>
			<div class="pied__actions">
				<a class="btn btn--or" href="<?php echo esc_url( SUEB_LIENS['preinscription'] ); ?>">Préinscription en ligne<?php echo sueb_icone( 'fleche', 18 ); ?></a>
				<a class="btn btn--verre" href="mailto:<?php echo esc_attr( $u['email'] ); ?>"><?php echo sueb_icone( 'courriel', 18 ); ?>Nous écrire</a>
			</div>
		</div>

		<div class="pied__grille">
			<div class="pied__marque">
				<a class="pied__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Université d’Ebolowa, accueil">
					<span class="pied__medaille"><img src="<?php echo esc_url( sueb_logo() ); ?>" alt="" width="64" height="62" loading="lazy"></span>
					<span class="pied__nom">Université d’Ebolowa<small>The University of Ebolowa</small></span>
				</a>
				<p class="pied__propos">Neuf établissements à Ebolowa, Sangmélima, Ambam et Kribi pour former, chercher et entreprendre au cœur de la région du Sud.</p>
				<ul class="pied__reseaux">
					<li><a href="<?php echo esc_url( SUEB_LIENS['facebook'] ); ?>" target="_blank" rel="noopener" aria-label="L’UEb sur Facebook"><?php echo sueb_icone( 'facebook', 20 ); ?></a></li>
					<li><a href="<?php echo esc_url( SUEB_LIENS['youtube'] ); ?>" target="_blank" rel="noopener" aria-label="L’UEb sur YouTube"><?php echo sueb_icone( 'youtube', 20 ); ?></a></li>
				</ul>
			</div>

			<nav class="pied__colonne" aria-labelledby="pied-universite">
				<h2 class="pied__titre" id="pied-universite">L’université</h2>
				<ul class="pied__liens">
					<li><a href="<?php echo esc_url( $base . '#message' ); ?>">Message du Recteur</a></li>
					<li><a href="<?php echo esc_url( $base . '#histoire' ); ?>">Histoire et création</a></li>
					<li><a href="<?php echo esc_url( sueb_url_dirigeants() ); ?>">Les dirigeants</a></li>
					<li><a href="<?php echo esc_url( $base . '#personnel' ); ?>">Personnel</a></li>
					<li><a href="<?php echo esc_url( $base . '#partenariats' ); ?>">Partenariats</a></li>
				</ul>
			</nav>

			<nav class="pied__colonne" aria-labelledby="pied-etudier">
				<h2 class="pied__titre" id="pied-etudier">Étudier à l’UEb</h2>
				<ul class="pied__liens">
					<li><a href="<?php echo esc_url( SUEB_LIENS['preinscription'] ); ?>">Préinscription en ligne</a></li>
					<li><a href="<?php echo esc_url( SUEB_LIENS['inscription'] ); ?>">Inscription administrative</a></li>
					<li><a href="<?php echo esc_url( $base . '#etablissements' ); ?>">Formations et syllabus</a></li>
					<li><a href="<?php echo esc_url( $base . '#actualites' ); ?>">Actualités</a></li>
					<li><a href="<?php echo esc_url( $base . '#agenda' ); ?>">Agenda</a></li>
				</ul>
			</nav>

			<div class="pied__colonne">
				<h2 class="pied__titre">Nous joindre</h2>
				<ul class="pied__contacts">
					<li><span class="pied__icone"><?php echo sueb_icone( 'lieu', 18 ); ?></span><span><?php echo esc_html( $u['bp'] ); ?><br><?php echo esc_html( $u['ville'] ); ?></span></li>
					<li><span class="pied__icone"><?php echo sueb_icone( 'telephone', 18 ); ?></span><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $u['tel'] ) ); ?>"><?php echo esc_html( $u['tel'] ); ?></a></li>
					<li><span class="pied__icone"><?php echo sueb_icone( 'courriel', 18 ); ?></span><a href="mailto:<?php echo esc_attr( $u['email'] ); ?>"><?php echo esc_html( $u['email'] ); ?></a></li>
				</ul>
			</div>
		</div>

		<div class="pied__etabs">
			<h2 class="pied__titre">Nos neuf établissements</h2>
			<ul>
				<?php foreach ( sueb_etablissements() as $sigle => $e ) : ?>
					<li><a href="<?php echo esc_url( sueb_lien_etablissement( $sigle ) ); ?>" title="<?php echo esc_attr( $e['fr'] ); ?>"><span class="pied__etab-logo"><img src="<?php echo esc_url( sueb_logo( $sigle ) ); ?>" alt="" width="28" height="28" loading="lazy"></span><?php echo esc_html( $sigle ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>

</footer>

<button class="haut-de-page" type="button" data-haut aria-label="Revenir en haut de la page"><?php echo sueb_icone( 'chevron', 22 ); ?></button>

<?php wp_footer(); ?>
</body>
</html>
