<?php
/**
 * Partenariats et conventions : les logos des institutions partenaires qui
 * flottent sur la page, puis un carrousel d'articles (photo, date de
 * signature, résumé).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$partenaires  = sueb_partenaires();
$institutions = sueb_institutions_partenaires();

/* Diamètre, décalage vertical (rem) et durée de flottement : chaque logo a son propre rythme. */
$tailles   = array( 140, 122, 150, 128, 146, 124, 136, 132 );
$decalages = array( 0, 2.75, .5, 3.25, 1.5, 0, 2.25, .75 );
$durees    = array( 7, 8.5, 6.5, 9, 7.5, 8, 6.8, 8.8 );
?>
<section class="section partenariats" id="partenariats" aria-labelledby="titre-part">
	<div class="conteneur">
		<header class="section__tete section__tete--ligne revele">
			<div>
				<p class="surtitre">Partenariats et conventions</p>
				<h2 id="titre-part">Ils avancent avec l’UEb</h2>
				<p class="section__chapo">Sept universités publiques partenaires et le ministère de l’Enseignement Supérieur, avec lequel l’UEb est liée par une convention : des échanges pour renforcer la recherche et les formations.</p>
			</div>
			<?php if ( count( $partenaires ) > 2 ) : ?>
				<div class="fleches" data-fleches="carrousel-partenaires">
					<button type="button" data-sens="-1" aria-label="Partenaires précédents"><?php echo sueb_icone( 'gauche', 22 ); ?></button>
					<button type="button" data-sens="1" aria-label="Partenaires suivants"><?php echo sueb_icone( 'droite', 22 ); ?></button>
				</div>
			<?php endif; ?>
		</header>
	</div>

	<div class="conteneur">
		<ul class="flotte" aria-label="Institutions partenaires">
			<?php foreach ( $institutions as $i => $inst ) :
				$logo = sueb_logo_partenaire( $inst['id'] );
				$n    = $i % count( $tailles );
				$conv = 'Convention' === $inst['type'];
				?>
				<li class="flotte__item revele" style="--t:<?php echo (int) $tailles[ $n ]; ?>px;--oy:<?php echo esc_attr( $decalages[ $n ] ); ?>rem;--dur:<?php echo esc_attr( $durees[ $n ] ); ?>s;--delai:-<?php echo esc_attr( round( $i * 1.3, 1 ) ); ?>s;--d:<?php echo esc_attr( $i * .08 ); ?>s">
					<div class="flotte__carte<?php echo $conv ? ' flotte__carte--convention' : ''; ?><?php echo $logo ? '' : ' flotte__carte--texte'; ?>">
						<?php if ( $logo ) : ?>
							<img src="<?php echo esc_url( $logo ); ?>" alt="" loading="lazy">
						<?php else : ?>
							<span class="flotte__sigle"><?php echo esc_html( $inst['sigle'] ); ?></span>
						<?php endif; ?>
					</div>
					<p class="flotte__nom"><span class="flotte__type"><?php echo esc_html( $inst['type'] ); ?></span><?php echo esc_html( $inst['nom'] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<?php if ( $partenaires ) : ?>
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