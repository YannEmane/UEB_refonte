<?php
/**
 * Vie étudiante sur l'accueil, après le personnel : quelques photos des campus en
 * mosaïque et un bouton vers la page Étudiants (page-etudiants.php). Le texte
 * reprend la présentation de cette page (réglage « etu_intro », gestion › Vie étudiante).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$intro  = trim( (string) strtok( (string) sueb_reglage( 'etu_intro' ), "\n" ) );
$photos = array(
	array( 'remise-toges', 'Remise des toges aux diplômés' ),
	array( 'vie-etudiante-4', 'Étudiants de l’Université d’Ebolowa' ),
	array( 'bibliotheque', 'La bibliothèque' ),
	array( 'amphi-cours', 'Un cours en amphithéâtre' ),
	array( 'campus-batiments', 'Les bâtiments du campus' ),
);
?>
<section class="section vie-etu" id="vie-etudiante" aria-labelledby="titre-vie-etu">
	<div class="conteneur">
		<header class="section__tete section__tete--ligne revele">
			<div>
				<p class="surtitre">Vie étudiante</p>
				<h2 id="titre-vie-etu">Étudier, vivre et grandir à l’UEb</h2>
				<?php if ( $intro ) : ?><p class="section__chapo"><?php echo esc_html( $intro ); ?></p><?php endif; ?>
			</div>
			<a class="btn btn--plein" href="<?php echo esc_url( sueb_url_etudiants() ); ?>">Découvrir la vie étudiante<?php echo sueb_icone( 'fleche', 18 ); ?></a>
		</header>

		<ul class="vie-etu__mosaique">
			<?php foreach ( $photos as $i => $p ) : ?>
				<li class="revele revele--zoom" style="--d:<?php echo esc_attr( round( $i * .08, 2 ) ); ?>s">
					<a href="<?php echo esc_url( sueb_url_etudiants() ); ?>" tabindex="-1" aria-hidden="true">
						<img src="<?php echo esc_url( sueb_photo( $p[0] ) ); ?>" alt="" loading="lazy" decoding="async">
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
