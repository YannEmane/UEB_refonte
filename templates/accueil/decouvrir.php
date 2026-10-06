<?php
/**
 * Découvrir l'UEb : message du Recteur, histoire, vidéo (les dirigeants ont leur propre page).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$dirigeants = sueb_dirigeants();
$recteur    = null;
foreach ( $dirigeants as $i => $d ) {
	if ( $d['recteur'] ) {
		$recteur = $d;
		unset( $dirigeants[ $i ] );
		break;
	}
}
$message  = sueb_page( 'message-du-recteur' );
$histoire = sueb_page( 'histoire' );
$editeur  = current_user_can( 'edit_posts' );
$arrete   = sueb_reglage( 'arrete' );

$jalons = array(
	array( '6 janvier 2022', 'Création', 'Le décret présidentiel n° 2022/009 crée l’Université d’Ebolowa, université d’État de la Région du Sud.' ),
);
if ( $arrete ) {
	$jalons[] = array( 'Arrêté ministériel', 'Organisation', $arrete );
} elseif ( $editeur ) {
	$jalons[] = array( 'Arrêté ministériel', 'À compléter', 'Référence et objet de l’arrêté à saisir dans Apparence › Personnaliser › Accueil UEb (visible des seuls éditeurs).' );
}
$jalons[] = array( '4 août 2022', 'Installation', 'Installation du Recteur et du Président du Conseil d’administration ; l’université commence ses activités.' );
$jalons[] = array( '18 novembre 2024', 'Ouverture', 'Convention-cadre de partenariat avec le Port Autonome de Kribi.' );
$jalons[] = array( '2026', 'Rayonnement', 'Nouvelles conventions, dont le ministère du Tourisme et des Loisirs et l’Institut Sapientia.' );

$video_fichier = (int) sueb_reglage( 'video_fichier' );
$video_url     = sueb_video_integree( (string) sueb_reglage( 'video_url' ) );
$affiche       = (int) sueb_reglage( 'video_affiche' );
$affiche       = $affiche ? wp_get_attachment_image_url( $affiche, 'full' ) : sueb_photo( 'diplomes' );
?>
<section class="section decouvrir" id="decouvrir" aria-labelledby="titre-decouvrir">
	<div class="conteneur">
		<header class="section__tete revele">
			<p class="surtitre">Découvrir l’UEb</p>
			<h2 id="titre-decouvrir">Une jeune université, ancrée dans son territoire</h2>
			<nav class="pastilles" aria-label="Dans cette rubrique">
				<a href="#message">Message du Recteur</a><a href="#histoire">Histoire</a><a href="<?php echo esc_url( sueb_url_dirigeants() ); ?>">Les dirigeants</a><a href="#video">Vidéo</a>
			</nav>
		</header>

		<!-- Message du Recteur -->
		<article class="message" id="message">
			<div class="message__portrait revele revele--gauche">
				<div class="message__cadre" data-parallaxe="0.06">
					<?php if ( $recteur && $recteur['photo'] ) : ?>
						<img src="<?php echo esc_url( $recteur['photo'] ); ?>" alt="<?php echo esc_attr( $recteur['nom'] ); ?>" loading="lazy">
					<?php else : ?>
						<span class="portrait-vide portrait-vide--grand" aria-hidden="true"><?php echo esc_html( sueb_initiales( $recteur['nom'] ?? 'UEb' ) ); ?></span>
					<?php endif; ?>
				</div>
				<span class="message__motif" aria-hidden="true"></span>
			</div>
			<div class="message__texte revele revele--droite">
				<p class="surtitre">Message du Recteur</p>
				<blockquote class="message__citation"><?php echo sueb_icone( 'guillemet', 44 ); ?><p><?php echo esc_html( sueb_reglage( 'recteur_citation' ) ); ?></p></blockquote>
				<?php if ( $message ) : ?>
					<p class="message__extrait"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $message->post_content ), 70 ) ); ?></p>
				<?php endif; ?>
				<?php if ( $recteur ) : ?>
					<p class="signature"><strong><?php echo esc_html( $recteur['nom'] ); ?></strong><span>Recteur de l’Université d’Ebolowa</span></p>
				<?php endif; ?>
				<?php if ( $message ) : ?>
					<a class="lien-fleche" href="<?php echo esc_url( get_permalink( $message ) ); ?>">Lire le message complet<?php echo sueb_icone( 'fleche', 18 ); ?></a>
				<?php endif; ?>
			</div>
		</article>
	</div>

	<!-- Histoire -->
	<div class="histoire" id="histoire">
		<div class="histoire__fond" aria-hidden="true" data-parallaxe="-0.08" style="background-image:url('<?php echo esc_url( sueb_photo( 'campus-ebolowa' ) ); ?>')"></div>
		<div class="conteneur histoire__grille">
			<div class="histoire__intro revele">
				<p class="surtitre surtitre--clair">Histoire de la création</p>
				<h3 class="histoire__titre">Née d’un décret, portée par une région</h3>
				<p><?php echo esc_html( $histoire ? wp_trim_words( wp_strip_all_tags( $histoire->post_content ), 60 ) : 'L’Université d’Ebolowa a été créée par le décret présidentiel n° 2022/009 du 6 janvier 2022.' ); ?></p>
				<?php if ( $histoire ) : ?>
					<a class="lien-fleche lien-fleche--clair" href="<?php echo esc_url( get_permalink( $histoire ) ); ?>">Toute l’histoire et les textes fondateurs<?php echo sueb_icone( 'fleche', 18 ); ?></a>
				<?php endif; ?>
			</div>
			<ol class="frise" data-frise>
				<?php foreach ( $jalons as $i => $j ) : ?>
					<li class="frise__jalon revele" style="--d:<?php echo esc_attr( $i * .12 ); ?>s">
						<span class="frise__date"><?php echo esc_html( $j[0] ); ?></span>
						<strong><?php echo esc_html( $j[1] ); ?></strong>
						<p><?php echo esc_html( $j[2] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</div>

	<!-- Vidéo de présentation -->
	<div class="conteneur">
		<div class="video revele revele--zoom" id="video">
			<img class="video__affiche" src="<?php echo esc_url( $affiche ); ?>" alt="" loading="lazy" data-parallaxe="0.05">
			<div class="video__voile" aria-hidden="true"></div>
			<div class="video__contenu">
				<p class="surtitre surtitre--clair">Vidéo de présentation</p>
				<h3><?php echo esc_html( sueb_reglage( 'video_titre' ) ); ?></h3>
				<p><?php echo esc_html( sueb_reglage( 'video_texte' ) ); ?></p>
			</div>
			<button class="video__lecture" type="button" data-ouvrir-video aria-label="Lire la vidéo de présentation">
				<span class="video__ondes" aria-hidden="true"></span><?php echo sueb_icone( 'lecture', 34 ); ?>
			</button>
		</div>
	</div>
</section>

<dialog class="modale" id="modale-video" aria-label="Vidéo de présentation de l’UEb"
	data-video-src="<?php echo esc_attr( $video_fichier ? (string) wp_get_attachment_url( $video_fichier ) : '' ); ?>"
	data-video-integre="<?php echo esc_attr( $video_url ); ?>">
	<button class="modale__fermer" type="button" data-fermer aria-label="Fermer"><?php echo sueb_icone( 'fermer', 24 ); ?></button>
	<div class="modale__media" data-media>
		<?php if ( ! $video_fichier && ! $video_url ) : ?>
			<p class="modale__vide">La vidéo de présentation sera bientôt en ligne.<?php echo $editeur ? '<br><small>Éditeurs : ajoutez-la dans Apparence › Personnaliser › Accueil UEb.</small>' : ''; ?></p>
		<?php endif; ?>
	</div>
</dialog>