<?php
/**
 * Bannière plein écran : diaporama lent (effet Ken Burns), titre animé,
 * puis l'université en chiffres.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$arbre  = $args['arbre'];
$villes = array_unique( array_filter( array_column( $arbre, 'ville' ) ) );
$slides = array(
	array( 'remise-toges', 'Remise des toges' ),
	array( 'campus-ebolowa', 'Le campus d’Ebolowa' ),
	array( 'equipe-fs', 'Faculté des Sciences' ),
	array( 'visite-port', 'ENSTMO, au port de Kribi' ),
);
$mots = preg_split( '/\s+/u', sueb_reglage( 'accueil_titre' ) );
?>
<section class="banniere" data-diaporama aria-label="Présentation">
	<div class="banniere__images">
		<?php foreach ( $slides as $i => $s ) : ?>
			<figure class="banniere__image<?php echo 0 === $i ? ' est-active' : ''; ?>" data-diapo>
				<img src="<?php echo esc_url( sueb_photo( $s[0] ) ); ?>" alt="" <?php echo 0 === $i ? 'fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async">
				<figcaption><?php echo esc_html( $s[1] ); ?></figcaption>
			</figure>
		<?php endforeach; ?>
	</div>
	<div class="banniere__voile" aria-hidden="true"></div>

	<div class="conteneur banniere__contenu">
		<p class="surtitre surtitre--clair anime" style="--d:.1s"><?php echo esc_html( sueb_reglage( 'accueil_surtitre' ) ); ?></p>
		<h1 class="banniere__titre">
			<?php foreach ( $mots as $i => $m ) : ?><span class="mot"><span style="--d:<?php echo esc_attr( .25 + $i * .07 ); ?>s"><?php echo esc_html( $m ); ?></span></span> <?php endforeach; ?>
		</h1>
		<p class="banniere__texte anime" style="--d:.9s"><?php echo esc_html( sueb_reglage( 'accueil_texte' ) ); ?></p>
		<div class="banniere__actions anime" style="--d:1.05s">
			<a class="btn btn--or" href="#etablissements">Nos établissements<?php echo sueb_icone( 'fleche', 18 ); ?></a>
			<button class="btn btn--verre" type="button" data-ouvrir-video><?php echo sueb_icone( 'lecture', 16 ); ?>Voir la vidéo</button>
		</div>
	</div>

	<div class="conteneur banniere__pied">
		<ol class="banniere__puces" aria-label="Images">
			<?php foreach ( $slides as $i => $s ) : ?>
				<li><button type="button" data-puce="<?php echo (int) $i; ?>"<?php echo 0 === $i ? ' aria-current="true"' : ''; ?>><span class="sr"><?php echo esc_html( $s[1] ); ?></span><i></i></button></li>
			<?php endforeach; ?>
		</ol>
		<a class="banniere__defiler" href="#chiffres"><span>Défiler</span><i aria-hidden="true"></i></a>
	</div>
</section>

<section class="chiffres" id="chiffres" aria-label="L’UEb en chiffres">
	<div class="conteneur chiffres__grille">
		<div class="chiffre revele"><strong data-compteur="2022">2022</strong><span>année de création, par décret présidentiel</span></div>
		<div class="chiffre revele" style="--d:.08s"><strong data-compteur="<?php echo count( $arbre ) ?: 9; ?>"><?php echo count( $arbre ) ?: 9; ?></strong><span>établissements : facultés, écoles et institut</span></div>
		<div class="chiffre revele" style="--d:.16s"><strong data-compteur="<?php echo count( $villes ) ?: 4; ?>"><?php echo count( $villes ) ?: 4; ?></strong><span>villes du Sud : <?php echo esc_html( implode( ', ', $villes ) ); ?></span></div>
		<div class="chiffre revele" style="--d:.24s"><strong data-compteur="<?php echo (int) wp_count_posts( 'ueb_partenaire' )->publish; ?>"><?php echo (int) wp_count_posts( 'ueb_partenaire' )->publish; ?></strong><span>conventions de partenariat signées</span></div>
	</div>
</section>
