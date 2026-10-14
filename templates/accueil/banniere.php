<?php
/**
 * Bannière de l'accueil : carrousel « à la une » des actualités et des
 * évènements (image à gauche coupée en biais, texte sur panneau à droite),
 * puis l'université en chiffres.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$arbre  = $args['arbre'];
$villes = array_unique( array_filter( array_column( $arbre, 'ville' ) ) );
$slides = sueb_slides_banniere( 5, $arbre );
$total  = count( $slides );
?>
<section class="banniere" data-carrousel aria-roledescription="carrousel" aria-label="À la une">
	<h1 class="sr">Université d’Ebolowa : à la une</h1>

	<div class="banniere__scene">
		<?php foreach ( $slides as $i => $s ) : ?>
			<article class="diapo<?php echo 0 === $i ? ' est-active' : ''; ?>" data-diapo role="group" aria-roledescription="diapositive" aria-label="<?php echo esc_attr( ( $i + 1 ) . ' sur ' . $total ); ?>"<?php echo 0 === $i ? '' : ' inert'; ?>>
				<figure class="diapo__image">
					<img src="<?php echo esc_url( $s['photo'] ); ?>" alt="" width="1400" height="940" decoding="async" <?php echo 0 === $i ? 'fetchpriority="high"' : 'loading="lazy"'; ?>>
				</figure>
				<div class="diapo__texte">
					<p class="diapo__etiquette">
						<span><?php echo esc_html( $s['etiquette'] ); ?></span>
						<?php if ( $s['date'] ) : ?><time datetime="<?php echo esc_attr( $s['iso'] ); ?>"><?php echo esc_html( $s['date'] ); ?></time><?php endif; ?>
						<?php if ( $s['lieu'] ) : ?><span class="diapo__lieu"><?php echo sueb_icone( 'lieu', 14 ); ?><?php echo esc_html( $s['lieu'] ); ?></span><?php endif; ?>
					</p>
					<h2 class="diapo__titre"><?php echo esc_html( $s['titre'] ); ?></h2>
					<?php if ( $s['texte'] ) : ?><p class="diapo__resume"><?php echo esc_html( $s['texte'] ); ?></p><?php endif; ?>
					<a class="btn btn--or diapo__lien" href="<?php echo esc_url( $s['url'] ); ?>"><?php echo esc_html( $s['lien'] ); ?><?php echo sueb_icone( 'fleche', 18 ); ?></a>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<?php if ( $total > 1 ) : ?>
		<div class="banniere__commandes">
			<ol class="banniere__traits" aria-label="Choisir une diapositive">
				<?php foreach ( $slides as $i => $s ) : ?>
					<li><button type="button" data-trait="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( ( $i + 1 ) . ' : ' . $s['titre'] ); ?>"<?php echo 0 === $i ? ' aria-current="true"' : ''; ?>><i></i></button></li>
				<?php endforeach; ?>
			</ol>
			<button class="banniere__pause" type="button" data-pause aria-label="Mettre le défilement en pause">
				<svg class="icone" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z" fill="currentColor"/></svg>
			</button>
		</div>
	<?php endif; ?>
</section>

<section class="chiffres" id="chiffres" aria-label="L’UEb en chiffres">
	<div class="conteneur chiffres__grille">
		<div class="chiffre revele"><strong data-compteur="2022">2022</strong><span>année de création, par décret présidentiel</span></div>
		<div class="chiffre revele" style="--d:.08s"><strong data-compteur="<?php echo count( $arbre ) ?: 9; ?>"><?php echo count( $arbre ) ?: 9; ?></strong><span>établissements : facultés, écoles et institut</span></div>
		<div class="chiffre revele" style="--d:.16s"><strong data-compteur="<?php echo count( $villes ) ?: 4; ?>"><?php echo count( $villes ) ?: 4; ?></strong><span>villes du Sud : <?php echo esc_html( implode( ', ', $villes ) ); ?></span></div>
		<div class="chiffre revele" style="--d:.24s"><strong data-compteur="<?php echo (int) wp_count_posts( 'ueb_partenaire' )->publish; ?>"><?php echo (int) wp_count_posts( 'ueb_partenaire' )->publish; ?></strong><span>conventions de partenariat signées</span></div>
	</div>
</section>