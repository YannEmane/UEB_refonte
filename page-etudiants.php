<?php
/**
 * La page Étudiants (/etudiants/, page WordPress « etudiants ») : la vie étudiante
 * sur les campus. Bandeau photo, rubriques (associations, sport, culture, logement…),
 * les quatre campus et leurs établissements, une galerie, les dernières actualités
 * et l'appel à s'inscrire. La présentation, les rubriques et la photo sont des
 * réglages, modifiables dans la gestion › Vie étudiante (inc/personnaliser.php).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

the_post();

$lignes = function ( $cle ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\R/u', (string) sueb_reglage( $cle ) ) ) ) );
};

$intro     = $lignes( 'etu_intro' );
$rubriques = array();
foreach ( $lignes( 'etu_rubriques' ) as $l ) {
	$p           = array_map( 'trim', explode( '|', $l, 2 ) );
	$rubriques[] = array( 'titre' => $p[0], 'texte' => $p[1] ?? '' );
}
$photo_id = (int) sueb_reglage( 'etu_photo' );
$photo    = $photo_id ? (string) wp_get_attachment_image_url( $photo_id, 'sueb-banniere' ) : sueb_photo( 'vie-etudiante-4' );

/* Les campus : chaque ville et ses établissements, lus dans l'offre de formation. */
$photos_campus = array( 'Ebolowa' => 'campus-ebolowa', 'Sangmélima' => 'campus-sangmelima', 'Ambam' => 'campus-ambam', 'Kribi' => 'enstmo-campus' );
$campus        = array();
foreach ( sueb_arbre_formation() as $e ) {
	$ville = $e['ville'] ?: 'Ebolowa';
	$campus[ $ville ][] = array(
		'sigle'   => $e['sigle'],
		'nom'     => $e['titre'],
		'couleur' => $e['couleur'],
		'logo'    => $e['logo'],
		'url'     => empty( $e['provisoire'] ) ? $e['url'] : sueb_lien_etablissement( $e['sigle'] ),
	);
}
uksort( $campus, function ( $a, $b ) use ( $campus ) {
	return array( -count( $campus[ $a ] ), $a ) <=> array( -count( $campus[ $b ] ), $b );
} );

$galerie  = array( 'remise-toges', 'vie-etudiante-4', 'bibliotheque', 'amphi-cours' );
$articles = get_posts( array( 'post_type' => 'post', 'numberposts' => 3 ) );

get_header();
?>
<header class="etu-tete" style="--photo:url('<?php echo esc_url( $photo ); ?>')">
	<div class="conteneur etu-tete__int">
		<ol class="fil-ariane"><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li><li aria-current="page"><?php echo esc_html( get_the_title() ); ?></li></ol>
		<h1 class="etu-tete__titre"><?php echo esc_html( get_the_title() ); ?></h1>
		<?php foreach ( $intro as $para ) : ?>
			<p class="etu-tete__chapo"><?php echo esc_html( $para ); ?></p>
		<?php endforeach; ?>
		<div class="etu-tete__actions">
			<?php if ( $rubriques ) : ?><a class="btn btn--or" href="#vivre">Vivre l’UEb<?php echo sueb_icone( 'chevron', 18 ); ?></a><?php endif; ?>
			<a class="btn btn--verre" href="#campus">Les campus</a>
		</div>
	</div>
</header>

<div class="acad-corps etu-corps">
	<div class="conteneur">
		<?php if ( $rubriques ) : ?>
			<section class="acad-famille" id="vivre" aria-labelledby="titre-vivre">
				<header class="acad-famille__tete revele">
					<h2 id="titre-vivre">Vivre l’UEb</h2>
					<p>Ce qui vous attend en dehors des cours.</p>
				</header>
				<ul class="etu-grille">
					<?php foreach ( $rubriques as $i => $r ) : ?>
						<li class="etu-rubrique revele" style="--d:<?php echo esc_attr( round( ( $i % 3 ) * .07, 2 ) ); ?>s">
							<span class="etu-rubrique__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<h3><?php echo esc_html( $r['titre'] ); ?></h3>
							<?php if ( $r['texte'] ) : ?><p><?php echo esc_html( $r['texte'] ); ?></p><?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<section class="acad-famille etu-campus" id="campus" aria-labelledby="titre-campus">
			<header class="acad-famille__tete revele">
				<h2 id="titre-campus">Les campus <span><?php echo count( $campus ); ?></span></h2>
				<p><?php $nb_etabs = array_sum( array_map( 'count', $campus ) ); echo esc_html( sprintf( _n( '%d ville', '%d villes', count( $campus ) ), count( $campus ) ) . ' de la Région du Sud, ' . sprintf( _n( '%d établissement', '%d établissements', $nb_etabs ), $nb_etabs ) . '. Choisissez-en un pour découvrir ses formations.' ); ?></p>
			</header>
			<ul class="etu-campus__grille">
				<?php foreach ( $campus as $ville => $etabs ) : ?>
					<li class="etu-ville revele">
						<img class="etu-ville__photo" src="<?php echo esc_url( sueb_photo( $photos_campus[ $ville ] ?? 'campus-batiments' ) ); ?>" alt="<?php echo esc_attr( 'Campus de ' . $ville ); ?>" width="960" height="640" loading="lazy">
						<div class="etu-ville__texte">
							<h3><?php echo sueb_icone( 'lieu', 20 ); ?><?php echo esc_html( $ville ); ?></h3>
							<ul class="etu-ville__etabs">
								<?php foreach ( $etabs as $e ) : ?>
									<li><a href="<?php echo esc_url( $e['url'] ); ?>" style="--etab:<?php echo esc_attr( $e['couleur'] ); ?>" title="<?php echo esc_attr( $e['nom'] ); ?>"><img src="<?php echo esc_url( $e['logo'] ); ?>" alt="" width="24" height="24" loading="lazy"><?php echo esc_html( $e['sigle'] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="acad-famille etu-galerie" aria-labelledby="titre-galerie">
			<header class="acad-famille__tete revele">
				<h2 id="titre-galerie">En images</h2>
			</header>
			<ul class="etu-galerie__grille">
				<?php foreach ( $galerie as $i => $nom ) : ?>
					<li class="revele revele--zoom" style="--d:<?php echo esc_attr( round( $i * .07, 2 ) ); ?>s"><img src="<?php echo esc_url( sueb_photo( $nom ) ); ?>" alt="" loading="lazy"></li>
				<?php endforeach; ?>
			</ul>
		</section>

		<?php if ( $articles ) : ?>
			<section class="acad-famille etu-actus" aria-labelledby="titre-etu-actus">
				<header class="acad-famille__tete revele">
					<h2 id="titre-etu-actus">Les dernières nouvelles</h2>
					<a class="lien-fleche" href="<?php echo esc_url( sueb_url_actualites() ); ?>">Toutes les actualités<?php echo sueb_icone( 'fleche', 16 ); ?></a>
				</header>
				<ul class="cartes-actu">
					<?php foreach ( $articles as $a ) : ?>
						<?php echo sueb_carte_actualite( $a ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<aside class="acad-appel revele">
			<div>
				<h2>Votre parcours commence à l’UEb</h2>
				<p>Préinscription et inscription administrative se font en ligne.</p>
			</div>
			<div class="etu-appel__actions">
				<a class="btn btn--or" href="<?php echo esc_url( sueb_lien( 'preinscription' ) ); ?>">Préinscription en ligne<?php echo sueb_icone( 'fleche', 18 ); ?></a>
				<a class="btn btn--verre" href="<?php echo esc_url( sueb_lien( 'inscription' ) ); ?>">Inscription administrative</a>
			</div>
		</aside>
	</div>
</div>
<?php
get_footer();
