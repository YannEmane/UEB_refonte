<?php
/**
 * Partenariats et conventions : défilé des logos, puis un carrousel
 * d'articles (photo, date de signature, résumé).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$partenaires = sueb_partenaires();
?>
<section class="section partenariats" id="partenariats" aria-labelledby="titre-part">
	<div class="conteneur">
		<header class="section__tete section__tete--ligne revele">
			<div>
				<p class="surtitre">Partenariats et conventions</p>
				<h2 id="titre-part">Ils avancent avec l’UEb</h2>
				<p class="section__chapo">Entreprises, institutions de recherche et ministères : des conventions pour professionnaliser les formations et ouvrir nos étudiants au monde du travail.</p>
			</div>
			<?php if ( count( $partenaires ) > 2 ) : ?>
				<div class="fleches" data-fleches="carrousel-partenaires">
					<button type="button" data-sens="-1" aria-label="Partenaires précédents"><?php echo sueb_icone( 'gauche', 22 ); ?></button>
					<button type="button" data-sens="1" aria-label="Partenaires suivants"><?php echo sueb_icone( 'droite', 22 ); ?></button>
				</div>
			<?php endif; ?>
		</header>
	</div>

	<?php if ( ! $partenaires ) : ?>
		<div class="conteneur"><p class="vide">Les conventions de partenariat seront bientôt présentées ici.</p></div>
	<?php else : ?>
		<div class="defile" aria-hidden="true">
			<div class="defile__piste">
				<?php for ( $tour = 0; $tour < 2; $tour++ ) : foreach ( $partenaires as $p ) : ?>
					<span class="defile__item"><?php if ( $p['logo'] ) : ?><img src="<?php echo esc_url( $p['logo'] ); ?>" alt="" loading="lazy"><?php endif; ?><?php echo esc_html( $p['titre'] ); ?></span>
				<?php endforeach; endfor; ?>
			</div>
		</div>

		<div class="carrousel" id="carrousel-partenaires" tabindex="0" aria-label="Partenaires">
			<ul class="carrousel__piste">
				<?php foreach ( $partenaires as $i => $p ) : ?>
					<li class="partenaire revele" style="--d:<?php echo esc_attr( min( $i, 4 ) * .08 ); ?>s">
						<a class="partenaire__lien" href="<?php echo esc_url( $p['url'] ); ?>">
							<div class="partenaire__visuel<?php echo $p['photo'] ? '' : ' partenaire__visuel--logo'; ?>">
								<?php if ( $p['photo'] ) : ?>
									<img class="partenaire__photo" src="<?php echo esc_url( $p['photo'] ); ?>" alt="" loading="lazy">
								<?php endif; ?>
								<?php if ( $p['logo'] ) : ?>
									<span class="partenaire__logo"><img src="<?php echo esc_url( $p['logo'] ); ?>" alt="" loading="lazy"></span>
								<?php else : ?>
									<span class="partenaire__logo partenaire__logo--texte"><?php echo esc_html( sueb_initiales( $p['titre'] ) ); ?></span>
								<?php endif; ?>
							</div>
							<div class="partenaire__corps">
								<p class="partenaire__meta">
									<?php if ( $p['type'] ) : ?><span><?php echo esc_html( $p['type'] ); ?></span><?php endif; ?>
									<?php if ( $p['signature'] ) : ?><time datetime="<?php echo esc_attr( $p['signature'] ); ?>">Signée le <?php echo esc_html( sueb_date( $p['signature'], 'j F Y' ) ); ?></time><?php endif; ?>
								</p>
								<h3><?php echo esc_html( $p['titre'] ); ?></h3>
								<p><?php echo esc_html( wp_trim_words( $p['texte'], 26 ) ); ?></p>
								<span class="lien-fleche">Lire l’article<?php echo sueb_icone( 'fleche', 18 ); ?></span>
							</div>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</section>
