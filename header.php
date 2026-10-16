<?php
/**
 * En-tête : bandeau institutionnel, logo, rubriques avec sous-menus.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$accueil = is_front_page();
$base    = $accueil ? '' : home_url( '/' );
$menu    = array(
	array( 'Découvrir l’UEb', '#decouvrir', array(
		array( 'Message du Recteur', '#message' ),
		array( 'Histoire et création', '#histoire' ),
		array( 'Les dirigeants', sueb_url_dirigeants() ),
		array( 'Vidéo de présentation', '#video' ),
	) ),
	array( 'Établissements et écoles', '#etablissements', array() ),
	array( 'Partenariats', '#partenariats', array() ),
	array( 'Nouvelles et évènements', '#actualites', array(
		array( 'Toutes les actualités', '#actualites' ),
		array( 'Tous les articles', sueb_url_actualites() ),
		array( 'Agenda', '#agenda' ),
	) ),
	array( 'Personnel', '#personnel', array() ),
);
foreach ( sueb_etablissements() as $sigle => $e ) {
	$menu[1][2][] = array( $sigle, sueb_lien_etablissement( $sigle ), $e['fr'] );
}
/* Icône de chaque entrée des sous-menus simples. */
$icones = array(
	'Message du Recteur'    => 'guillemet',
	'Histoire et création'  => 'livre',
	'Les dirigeants'        => 'personnes',
	'Vidéo de présentation' => 'video',
	'Toutes les actualités' => 'article',
	'Agenda'                => 'calendrier',
);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0f2c1f">
<link rel="icon" href="<?php echo esc_url( sueb_logo() ); ?>">
<?php wp_head(); ?>
</head>
<body <?php body_class( $accueil ? 'page-accueil' : '' ); ?>>
<?php wp_body_open(); ?>
<a class="lien-evitement" href="#contenu">Aller au contenu</a>

<div class="bandeau-haut">
	<div class="conteneur bandeau-haut__int">
		<p class="bandeau-haut__devise"><span>République du Cameroun</span><em>Paix – Travail – Patrie</em></p>
		<ul class="bandeau-haut__liens">
			<li class="bandeau-haut__acces"><a href="<?php echo esc_url( SUEB_LIENS['preinscription'] ); ?>">Préinscription</a></li>
			<li class="bandeau-haut__acces"><a href="<?php echo esc_url( SUEB_LIENS['inscription'] ); ?>">Inscription</a></li>
			<li><a href="<?php echo esc_url( $base . '#agenda' ); ?>">Agenda</a></li>
			<li><a href="<?php echo esc_url( $base . '#contact' ); ?>">Contact</a></li>
		</ul>
	</div>
</div>

<header class="entete<?php echo $accueil ? ' entete--transparent' : ''; ?>" data-entete>
	<div class="conteneur entete__int">
		<a class="marque" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Université d’Ebolowa, accueil">
			<img class="marque__logo" src="<?php echo esc_url( sueb_logo() ); ?>" alt="" width="56" height="54">
			<span class="marque__nom">
				<strong>Université d’Ebolowa</strong>
				<span>The University of Ebolowa</span>
			</span>
		</a>

		<nav class="nav" id="nav" aria-label="Rubriques">
			<ul class="nav__liste">
				<?php foreach ( $menu as $i => $item ) : ?>
					<li class="nav__item<?php echo $item[2] ? ' nav__item--sous' : ''; ?>">
						<a class="nav__lien" href="<?php echo esc_url( $base . $item[1] ); ?>"><?php echo esc_html( $item[0] ); ?><?php echo $item[2] ? sueb_icone( 'chevron', 14 ) : ''; ?></a>
						<?php if ( $item[2] ) : ?>
							<div class="sous-menu<?php echo 1 === $i ? ' sous-menu--large' : ''; ?>">
								<ul>
									<?php foreach ( $item[2] as $k => $s ) : ?>
										<li style="--i:<?php echo (int) $k; ?>"><a href="<?php echo esc_url( 0 === strpos( $s[1], '#' ) ? $base . $s[1] : $s[1] ); ?>"><?php if ( isset( $s[2] ) ) : ?><span class="sous-menu__logo"><img src="<?php echo esc_url( sueb_logo( $s[0] ) ); ?>" alt="" width="30" height="30" loading="lazy"></span><span class="sous-menu__texte"><strong><?php echo esc_html( $s[0] ); ?></strong><small><?php echo esc_html( $s[2] ); ?></small></span><?php else : ?><span class="sous-menu__icone"><?php echo sueb_icone( $icones[ $s[0] ] ?? 'droite', 18 ); ?></span><?php echo esc_html( $s[0] ); ?><?php endif; ?></a></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
					</li>
					<?php if ( 0 === $i ) : ?>
						<li class="nav__item nav__item--mobile"><a class="nav__lien" href="<?php echo esc_url( SUEB_LIENS['preinscription'] ); ?>">Préinscription</a></li>
						<li class="nav__item nav__item--mobile"><a class="nav__lien" href="<?php echo esc_url( SUEB_LIENS['inscription'] ); ?>">Inscription</a></li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
		</nav>

		<button class="entete__menu" type="button" aria-expanded="false" aria-controls="nav" data-menu>
			<span class="sr">Menu</span><?php echo sueb_icone( 'menu', 24 ) . sueb_icone( 'fermer', 24 ); ?>
		</button>
	</div>
	<div class="entete__progression" data-progression aria-hidden="true"></div>
</header>

<main id="contenu">