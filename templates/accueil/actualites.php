<?php
/**
 * Nouvelles et évènements : une actualité à la une, les suivantes en
 * cartes (article, vidéo ou audio, filtrables), l'agenda à côté.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$actus      = sueb_actualites( 7 );
$evenements = sueb_evenements( 4 );
$une        = array_shift( $actus );
$libelles   = array( 'article' => 'Article', 'video' => 'Vidéo', 'audio' => 'Audio' );
$formats    = array_unique( array_merge( $une ? array( $une['format'] ) : array(), array_column( $actus, 'format' ) ) );

/** Le média d'une carte : lecteur audio en place, vidéo dans la modale. */
$media = function ( $a ) {
	if ( 'audio' === $a['format'] && $a['media'] ) {
		printf( '<div class="lecteur" data-lecteur><button type="button" class="lecteur__bouton" aria-label="Écouter : %s">%s</button><div class="lecteur__barre"><i></i></div><span class="lecteur__temps">%s</span><audio preload="none" src="%s"></audio></div>', esc_attr( $a['titre'] ), sueb_icone( 'lecture', 18 ), esc_html( $a['duree'] ?: '0:00' ), esc_url( $a['media'] ) );
	}
};
$attr_video = function ( $a ) {
	if ( 'video' !== $a['format'] || ( ! $a['media'] && ! $a['integre'] ) ) {
		return '';
	}
	return sprintf( ' data-video-src="%s" data-video-integre="%s"', esc_attr( $a['media'] ), esc_attr( $a['integre'] ) );
};
?>
<section class="section actualites" id="actualites" aria-labelledby="titre-actu">
	<div class="conteneur">
		<header class="section__tete section__tete--ligne revele">
			<div>
				<p class="surtitre">Nouvelles et évènements</p>
				<h2 id="titre-actu">La vie de l’université</h2>
			</div>
			<div class="filtres" role="group" aria-label="Filtrer les actualités" data-filtres-actu>
				<button type="button" aria-pressed="true" data-format="">Tout</button>
				<?php foreach ( $libelles as $f => $l ) : ?>
					<button type="button" aria-pressed="false" data-format="<?php echo esc_attr( $f ); ?>"<?php echo in_array( $f, $formats, true ) ? '' : ' hidden'; ?>><?php echo sueb_icone( $f, 16 ); ?><?php echo esc_html( 'article' === $f ? 'Articles' : ( 'video' === $f ? 'Vidéos' : 'Audios' ) ); ?></button>
				<?php endforeach; ?>
			</div>
		</header>

		<div class="actualites__grille">
			<div class="actualites__fil" data-fil-actu>
				<?php if ( ! $une ) : ?>
					<p class="vide">Les premières actualités arrivent bientôt.</p>
				<?php else : ?>
					<article class="une revele" data-format="<?php echo esc_attr( $une['format'] ); ?>">
						<a class="une__image" href="<?php echo esc_url( $une['url'] ); ?>"<?php echo $attr_video( $une ); ?>>
							<img src="<?php echo esc_url( $une['photo'] ); ?>" alt="" loading="lazy">
							<span class="badge-format badge-format--<?php echo esc_attr( $une['format'] ); ?>"><?php echo sueb_icone( $une['format'], 16 ); ?><?php echo esc_html( $libelles[ $une['format'] ] ); ?><?php echo $une['duree'] ? ' · ' . esc_html( $une['duree'] ) : ''; ?></span>
							<?php if ( 'video' === $une['format'] ) : ?><span class="lecture-ronde" aria-hidden="true"><?php echo sueb_icone( 'lecture', 28 ); ?></span><?php endif; ?>
						</a>
						<div class="une__corps">
							<p class="meta-actu"><time datetime="<?php echo esc_attr( $une['iso'] ); ?>"><?php echo esc_html( $une['date'] ); ?></time><?php echo $une['rubrique'] ? '<span>' . esc_html( $une['rubrique'] ) . '</span>' : ''; ?></p>
							<h3><a href="<?php echo esc_url( $une['url'] ); ?>"><?php echo esc_html( $une['titre'] ); ?></a></h3>
							<p><?php echo esc_html( $une['texte'] ); ?></p>
							<?php $media( $une ); ?>
						</div>
					</article>

					<ul class="cartes-actu">
						<?php foreach ( $actus as $i => $a ) : ?>
							<li class="carte-actu revele" style="--d:<?php echo esc_attr( ( $i % 3 ) * .08 ); ?>s" data-format="<?php echo esc_attr( $a['format'] ); ?>">
								<a class="carte-actu__image" href="<?php echo esc_url( $a['url'] ); ?>"<?php echo $attr_video( $a ); ?>>
									<img src="<?php echo esc_url( $a['photo'] ); ?>" alt="" loading="lazy">
									<span class="badge-format badge-format--<?php echo esc_attr( $a['format'] ); ?>"><?php echo sueb_icone( $a['format'], 14 ); ?><?php echo esc_html( $libelles[ $a['format'] ] ); ?></span>
									<?php if ( 'video' === $a['format'] ) : ?><span class="lecture-ronde lecture-ronde--petite" aria-hidden="true"><?php echo sueb_icone( 'lecture', 20 ); ?></span><?php endif; ?>
								</a>
								<p class="meta-actu"><time datetime="<?php echo esc_attr( $a['iso'] ); ?>"><?php echo esc_html( $a['date'] ); ?></time><?php echo $a['rubrique'] ? '<span>' . esc_html( $a['rubrique'] ) . '</span>' : ''; ?></p>
								<h3><a href="<?php echo esc_url( $a['url'] ); ?>"><?php echo esc_html( $a['titre'] ); ?></a></h3>
								<?php $media( $a ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
					<p class="vide" data-vide-filtre hidden>Aucune actualité de ce format pour l’instant.</p>
				<?php endif; ?>
			</div>

			<aside class="agenda revele revele--droite" id="agenda" aria-labelledby="titre-agenda">
				<h3 id="titre-agenda" class="agenda__titre"><?php echo sueb_icone( 'calendrier', 22 ); ?>Agenda</h3>
				<?php if ( ! $evenements ) : ?>
					<p class="agenda__vide">Aucun évènement programmé pour le moment.</p>
				<?php else : ?>
					<ol class="agenda__liste">
						<?php foreach ( $evenements as $ev ) : ?>
							<li class="evenement<?php echo $ev['passe'] ? ' evenement--passe' : ''; ?>">
								<time class="evenement__date" datetime="<?php echo esc_attr( $ev['iso'] ); ?>"><strong><?php echo esc_html( $ev['jour'] ); ?></strong><?php echo esc_html( $ev['mois'] ); ?></time>
								<div>
									<a class="evenement__titre" href="<?php echo esc_url( $ev['url'] ); ?>"><?php echo esc_html( $ev['titre'] ); ?></a>
									<p class="evenement__infos"><?php echo esc_html( $ev['heure'] ); ?><?php echo $ev['lieu'] ? ' · ' . esc_html( $ev['lieu'] ) : ''; ?></p>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
				<a class="lien-fleche lien-fleche--clair" href="<?php echo esc_url( get_post_type_archive_link( 'ueb_evenement' ) ); ?>">Tout l’agenda<?php echo sueb_icone( 'fleche', 18 ); ?></a>
			</aside>
		</div>
	</div>
</section>
