<?php
/**
 * La page d'un évènement : un bandeau vert avec la date en grand (pastille or,
 * comme dans l'agenda), le titre, l'état (à venir, aujourd'hui, passé), l'horaire
 * et le lieu ; puis la description, avec l'image s'il y en a une, à côté d'un
 * encadré « Informations pratiques » (date, horaire, lieu, ajout à son agenda,
 * partage). Pour finir, les autres évènements à venir et le lien vers l'agenda
 * complet (page Actualités, #agenda).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$id     = get_the_ID();
	$titre  = get_the_title();
	$url    = get_permalink();
	$agenda = sueb_url_actualites() . '#agenda';

	/* Les dates saisies sont celles du site : on les lit dans son fuseau, sans décalage. */
	$fuseau = wp_timezone();
	$debut  = date_create_immutable( (string) sueb_meta( $id, 'debut' ), $fuseau ) ?: null;
	$fin    = (string) sueb_meta( $id, 'fin' ) ? ( date_create_immutable( (string) sueb_meta( $id, 'fin' ), $fuseau ) ?: null ) : null;
	if ( $fin && $debut && $fin < $debut ) {
		$fin = null;
	}
	$ts      = $debut ? $debut->getTimestamp() : 0;
	$ts_fin  = $fin ? $fin->getTimestamp() : 0;
	$lieu    = (string) sueb_meta( $id, 'lieu' );
	$maint   = current_datetime();
	$meme_j  = $fin && $debut && $fin->format( 'Y-m-d' ) === $debut->format( 'Y-m-d' );

	/* L'état : en cours ou aujourd'hui, à venir, passé. */
	if ( ! $debut ) {
		$etat = '';
	} elseif ( $debut->format( 'Y-m-d' ) === $maint->format( 'Y-m-d' ) || ( $fin && $debut <= $maint && $maint <= $fin ) ) {
		$etat = 'aujourdhui';
	} elseif ( $debut > $maint ) {
		$etat = 'a-venir';
	} else {
		$etat = 'passe';
	}
	$etats = array( 'aujourdhui' => 'Aujourd’hui', 'a-venir' => 'À venir', 'passe' => 'Évènement passé' );

	/* Date et horaire en toutes lettres. */
	$date_txt = $ts ? ucfirst( wp_date( 'l j F Y', $ts ) ) : '';
	$heure    = $ts ? wp_date( 'G\hi', $ts ) : '';
	if ( $fin && ! $meme_j ) {
		$date_txt = 'Du ' . wp_date( 'j F Y', $ts ) . ' au ' . wp_date( 'j F Y', $ts_fin );
		$horaire  = $heure ? 'À partir de ' . $heure : '';
	} else {
		$horaire = $heure . ( $fin ? ' – ' . wp_date( 'G\hi', $ts_fin ) : '' );
	}

	/* Ajout à Google Agenda (heure UTC ; sans heure de fin, deux heures par défaut). */
	$google = '';
	if ( $ts && 'passe' !== $etat ) {
		$google = add_query_arg( array(
			'action'   => 'TEMPLATE',
			'text'     => rawurlencode( $titre ),
			'dates'    => gmdate( 'Ymd\THis\Z', $ts ) . '/' . gmdate( 'Ymd\THis\Z', $ts_fin ?: $ts + 2 * HOUR_IN_SECONDS ),
			'location' => rawurlencode( $lieu ),
			'details'  => rawurlencode( $url ),
		), 'https://calendar.google.com/calendar/render' );
	}

	$photo   = has_post_thumbnail() ? (string) get_the_post_thumbnail_url( $id, 'sueb-banniere' ) : '';
	$contenu = trim( (string) get_the_content() );

	/* Les autres évènements à venir. */
	$autres = array_values( array_filter( sueb_agenda( 1 )['a_venir'], function ( $ev ) use ( $url ) {
		return $ev['url'] !== $url;
	} ) );
	$autres = array_slice( $autres, 0, 3 );
	?>
	<article class="evt" aria-labelledby="evt-titre">
		<header class="evt-tete">
			<div class="conteneur">
				<ol class="fil-ariane"><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li><li><a href="<?php echo esc_url( sueb_url_actualites() ); ?>">Actualités</a></li><li><a href="<?php echo esc_url( $agenda ); ?>">Agenda</a></li></ol>
				<div class="evt-tete__ligne">
					<?php if ( $ts ) : ?>
						<time class="evt-tete__date<?php echo 'passe' === $etat ? ' evt-tete__date--passe' : ''; ?>" datetime="<?php echo esc_attr( wp_date( 'c', $ts ) ); ?>">
							<span><?php echo esc_html( wp_date( 'l', $ts ) ); ?></span>
							<strong><?php echo esc_html( wp_date( 'j', $ts ) ); ?></strong>
							<span><?php echo esc_html( wp_date( 'F Y', $ts ) ); ?></span>
						</time>
					<?php endif; ?>
					<div class="evt-tete__texte">
						<?php if ( $etat ) : ?><p class="evt-etat evt-etat--<?php echo esc_attr( $etat ); ?>"><?php echo esc_html( $etats[ $etat ] ); ?></p><?php endif; ?>
						<h1 class="evt-tete__titre" id="evt-titre"><?php echo esc_html( $titre ); ?></h1>
						<p class="evt-tete__infos">
							<?php if ( $horaire ) : ?><span><?php echo sueb_icone( 'horloge', 20 ); ?><?php echo esc_html( $horaire ); ?></span><?php endif; ?>
							<?php if ( $lieu ) : ?><span><?php echo sueb_icone( 'lieu', 20 ); ?><?php echo esc_html( $lieu ); ?></span><?php endif; ?>
						</p>
					</div>
				</div>
			</div>
		</header>

		<div class="conteneur evt-grille">
			<div class="evt-principal">
				<?php if ( $photo ) : ?>
					<figure class="evt-image"><img src="<?php echo esc_url( $photo ); ?>" alt="" width="1400" height="940" fetchpriority="high" decoding="async"></figure>
				<?php endif; ?>
				<?php if ( $contenu ) : ?>
					<div class="article__corps evt-corps"><?php the_content(); ?></div>
				<?php else : ?>
					<p class="evt-vide">Le programme détaillé de cet évènement sera bientôt publié.</p>
				<?php endif; ?>
			</div>

			<aside class="evt-cote" aria-labelledby="evt-pratique">
				<div class="evt-pratique">
					<h2 id="evt-pratique">Informations pratiques</h2>
					<dl>
						<?php if ( $date_txt ) : ?><div><dt><?php echo sueb_icone( 'calendrier', 18 ); ?>Date</dt><dd><?php echo esc_html( $date_txt ); ?></dd></div><?php endif; ?>
						<?php if ( $horaire ) : ?><div><dt><?php echo sueb_icone( 'horloge', 18 ); ?>Horaire</dt><dd><?php echo esc_html( $horaire ); ?></dd></div><?php endif; ?>
						<?php if ( $lieu ) : ?><div><dt><?php echo sueb_icone( 'lieu', 18 ); ?>Lieu</dt><dd><?php echo esc_html( $lieu ); ?></dd></div><?php endif; ?>
					</dl>
					<?php if ( $google ) : ?>
						<a class="btn btn--or evt-pratique__btn" href="<?php echo esc_url( $google ); ?>" target="_blank" rel="noopener"><?php echo sueb_icone( 'calendrier', 18 ); ?>Ajouter à mon agenda</a>
					<?php endif; ?>
				</div>
				<div class="evt-partage">
					<h2>Partager</h2>
					<?php echo sueb_partage( $url, $titre ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?>
				</div>
				<a class="lien-fleche" href="<?php echo esc_url( $agenda ); ?>"><?php echo sueb_icone( 'gauche', 18 ); ?>Retour à l’agenda</a>
			</aside>
		</div>
	</article>

	<?php if ( $autres ) : ?>
		<section class="agenda-page evt-autres" aria-labelledby="evt-autres-titre">
			<div class="conteneur">
				<div class="actus-liste__tete">
					<h2 class="actus-liste__titre agenda-page__titre" id="evt-autres-titre"><?php echo sueb_icone( 'calendrier', 28 ); ?>Prochains évènements</h2>
					<a class="lien-fleche" href="<?php echo esc_url( $agenda ); ?>">Tout l’agenda<?php echo sueb_icone( 'fleche', 18 ); ?></a>
				</div>
				<ol class="agenda-page__liste">
					<?php foreach ( $autres as $ev ) : ?>
						<li class="agenda-carte">
							<time class="agenda-carte__date" datetime="<?php echo esc_attr( $ev['iso'] ); ?>"><strong><?php echo esc_html( $ev['jour'] ); ?></strong><?php echo esc_html( $ev['mois'] ); ?><small><?php echo esc_html( $ev['annee'] ); ?></small></time>
							<div class="agenda-carte__texte">
								<h3><a href="<?php echo esc_url( $ev['url'] ); ?>"><?php echo esc_html( $ev['titre'] ); ?></a></h3>
								<p class="agenda-carte__infos">
									<span><?php echo sueb_icone( 'horloge', 16 ); ?><?php echo esc_html( ucfirst( $ev['date'] ) . ( $ev['heure'] ? ', ' . $ev['heure'] : '' ) ); ?></span>
									<?php if ( $ev['lieu'] ) : ?><span><?php echo sueb_icone( 'lieu', 16 ); ?><?php echo esc_html( $ev['lieu'] ); ?></span><?php endif; ?>
								</p>
							</div>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</section>
	<?php endif; ?>

	<script type="application/ld+json"><?php
	echo wp_json_encode( array_filter( array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Event',
		'name'        => $titre,
		'startDate'   => $ts ? wp_date( 'c', $ts ) : '',
		'endDate'     => $ts_fin ? wp_date( 'c', $ts_fin ) : '',
		'location'    => $lieu ? array( '@type' => 'Place', 'name' => $lieu ) : '',
		'image'       => $photo,
		'description' => wp_trim_words( wp_strip_all_tags( $contenu ), 40 ),
		'organizer'   => array( '@type' => 'CollegeOrUniversity', 'name' => SUEB_UNIVERSITE['fr'], 'url' => home_url( '/' ) ),
	) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG );
	?></script>
	<?php
endwhile;

get_footer();
