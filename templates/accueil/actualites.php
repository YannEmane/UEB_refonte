<?php
/**
 * Nouvelles et évènements : une mosaïque de photos, dans l'esprit de « La vie
 * sur le campus » du site de préinscription. L'actualité à la une occupe la
 * grande tuile ; à tour de rôle, une autre vient prendre sa place et la une
 * part dans la petite tuile libérée (site.js). L'agenda en colonne à droite.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$actus      = array_slice( sueb_actualites( 5 ), 0, 5 );
$evenements = sueb_evenements( 3 );
$total      = count( $actus );
$libelles   = array( 'article' => 'Article', 'video' => 'Vidéo', 'audio' => 'Audio' );
$liens      = array( 'article' => 'Lire l’article', 'video' => 'Voir la vidéo', 'audio' => 'Écouter' );
$archive    = sueb_url_actualites();

/** Pastille du format (vidéo ou audio) posée sur la photo ; rien pour un article. */
$badge = function ( $a ) use ( $libelles ) {
	if ( 'article' === $a['format'] ) {
		return '';
	}
	return sueb_icone( $a['format'], 16 ) . esc_html( $libelles[ $a['format'] ] ) . ( $a['duree'] ? '<span>' . esc_html( $a['duree'] ) . '</span>' : '' );
};
/** Une vidéo s'ouvre dans la modale. */
$attr_video = function ( $a ) {
	if ( 'video' !== $a['format'] || ( ! $a['media'] && ! $a['integre'] ) ) {
		return '';
	}
	return sprintf( ' data-video-src="%s" data-video-integre="%s"', esc_attr( $a['media'] ), esc_attr( $a['integre'] ) );
};

/* Ce qu'il faut à site.js pour faire tourner les tuiles (textes décodés : site.js les écrit en texte brut). */
$donnees = array_map(
	function ( $a ) use ( $badge, $liens ) {
		return array(
			'titre'    => html_entity_decode( $a['titre'], ENT_QUOTES, 'UTF-8' ),
			'url'      => $a['url'],
			'photo'    => $a['photo'],
			'date'     => $a['date'],
			'iso'      => $a['iso'],
			'rubrique' => $a['rubrique'],
			'texte'    => html_entity_decode( $a['texte'], ENT_QUOTES, 'UTF-8' ),
			'format'   => $a['format'],
			'media'    => 'video' === $a['format'] ? $a['media'] : '',
			'integre'  => 'video' === $a['format'] ? $a['integre'] : '',
			'badge'    => $badge( $a ),
			'lien'     => $liens[ $a['format'] ],
		);
	},
	$actus
);
?>
<section class="section actualites" id="actualites" aria-labelledby="titre-actu">
	<div class="conteneur">
		<header class="section__tete section__tete--ligne revele">
			<div>
				<p class="surtitre">Nouvelles et évènements</p>
				<h2 id="titre-actu">La vie de l’université</h2>
			</div>
			<?php if ( $total > 1 || $archive ) : ?>
			<div class="actualites__actions">
				<?php if ( $total > 1 ) : ?>
					<button class="actualites__pause" type="button" data-pause-actus aria-label="Mettre le défilement en pause" hidden>
						<svg class="icone" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z" fill="currentColor"/></svg>
					</button>
				<?php endif; ?>
				<?php if ( $archive ) : ?>
					<a class="lien-fleche" href="<?php echo esc_url( $archive ); ?>">Toutes les actualités<?php echo sueb_icone( 'fleche', 18 ); ?></a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</header>

		<div class="actualites__corps">
		<?php if ( ! $actus ) : ?>
			<p class="vide">Les premières actualités arrivent bientôt.</p>
		<?php else : ?>
		<ul class="mosaique-actu mosaique-actu--<?php echo (int) $total; ?>" data-mosaique-actu>
			<?php foreach ( $actus as $i => $a ) : ?>
				<li class="tuile<?php echo 0 === $i ? ' tuile--une' : ''; ?> revele revele--zoom" style="--d:<?php echo esc_attr( round( $i * .08, 2 ) ); ?>s" data-tuile>
					<img class="tuile__photo est-active" src="<?php echo esc_url( $a['photo'] ); ?>" alt=""<?php echo 0 === $i ? '' : ' loading="lazy"'; ?> decoding="async">
					<span class="badge-format badge-format--<?php echo esc_attr( $a['format'] ); ?>" data-t-badge<?php echo 'article' === $a['format'] ? ' hidden' : ''; ?>><?php echo $badge( $a ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans $badge ?></span>
					<span class="lecture-ronde" aria-hidden="true" data-t-lecture<?php echo 'video' === $a['format'] ? '' : ' hidden'; ?>><?php echo sueb_icone( 'lecture', 24 ); ?></span>
					<div class="tuile__plaque">
						<?php if ( 0 === $i && $total > 1 ) : ?><span class="tuile__temps" aria-hidden="true"><i data-temps-actus></i></span><?php endif; ?>
						<p class="tuile__meta"><time datetime="<?php echo esc_attr( $a['iso'] ); ?>" data-t-date><?php echo esc_html( $a['date'] ); ?></time><span class="tuile__rubrique" data-t-rubrique<?php echo $a['rubrique'] ? '' : ' hidden'; ?>><?php echo esc_html( $a['rubrique'] ); ?></span></p>
						<h3 class="tuile__titre"><a class="tuile__lien" href="<?php echo esc_url( $a['url'] ); ?>"<?php echo $attr_video( $a ); ?> data-t-lien><?php echo esc_html( $a['titre'] ); ?></a></h3>
						<p class="tuile__resume" data-t-resume><?php echo esc_html( $a['texte'] ); ?></p>
						<span class="tuile__lire" aria-hidden="true"><span data-t-lire><?php echo esc_html( $liens[ $a['format'] ] ); ?></span><?php echo sueb_icone( 'droite', 18 ); ?></span>
					</div>
					<a class="tuile__cible" href="<?php echo esc_url( $a['url'] ); ?>"<?php echo $attr_video( $a ); ?> tabindex="-1" aria-hidden="true" data-t-cible></a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>

		<aside class="agenda revele revele--zoom" id="agenda" style="--d:.4s" aria-labelledby="titre-agenda">
			<h3 id="titre-agenda" class="agenda__titre"><?php echo sueb_icone( 'calendrier', 22 ); ?>Agenda</h3>
			<?php if ( ! $evenements ) : ?>
				<div class="agenda__vide">
					<p><strong>Aucun évènement programmé pour le moment.</strong></p>
					<p>Les prochaines rencontres, soutenances et cérémonies seront annoncées ici.</p>
				</div>
			<?php else : ?>
				<ol class="agenda__liste">
					<?php foreach ( $evenements as $ev ) : ?>
						<li class="evenement<?php echo $ev['passe'] ? ' evenement--passe' : ''; ?>">
							<time class="evenement__date" datetime="<?php echo esc_attr( $ev['iso'] ); ?>"><strong><?php echo esc_html( $ev['jour'] ); ?></strong><?php echo esc_html( $ev['mois'] ); ?></time>
							<div>
								<a class="evenement__titre" href="<?php echo esc_url( $ev['url'] ); ?>"><?php echo esc_html( $ev['titre'] ); ?></a>
								<p class="evenement__infos"><?php echo esc_html( $ev['heure'] ); ?><?php echo $ev['lieu'] ? ', ' . esc_html( $ev['lieu'] ) : ''; ?></p>
							</div>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
			<a class="lien-fleche lien-fleche--clair agenda__lien" href="<?php echo esc_url( get_post_type_archive_link( 'ueb_evenement' ) ); ?>">Tout l’agenda<?php echo sueb_icone( 'fleche', 18 ); ?></a>
		</aside>
		</div>
		<?php if ( $actus ) : ?>
		<script type="application/json" id="donnees-actus"><?php echo wp_json_encode( $donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ); ?></script>
		<?php endif; ?>
	</div>
</section>
