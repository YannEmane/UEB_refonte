<?php
/**
 * La page Contact (/contact/, page WordPress « contact ») : les coordonnées du
 * rectorat (réglages « Coordonnées » de la gestion), puis celles de chaque
 * établissement (champs Courriel, Téléphone et Adresse de sa fiche), avec son
 * doyen ou directeur. Le texte de la page, s'il y en a un, sert d'introduction.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

the_post();

$u     = sueb_universite();
$intro = trim( (string) get_the_content() );

/* Le Recteur, s'il est renseigné dans les dirigeants. */
$recteur = '';
foreach ( sueb_dirigeants() as $d ) {
	if ( $d['recteur'] ) {
		$recteur = $d['nom'];
		break;
	}
}

/* Le doyen ou directeur de chaque établissement (catégorie « Doyens et directeurs » du Personnel). */
$directions = array();
foreach ( get_posts( array(
	'post_type'   => 'ueb_personnel',
	'numberposts' => -1,
	'tax_query'   => array( array( 'taxonomy' => 'ueb_corps', 'field' => 'slug', 'terms' => 'doyens-directeurs' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery -- une dizaine de fiches
	'orderby'     => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
) ) as $p ) {
	$etab = (int) sueb_meta( $p->ID, 'etablissement' );
	if ( $etab && ! isset( $directions[ $etab ] ) && ! sueb_meta( $p->ID, 'demo' ) && 0 !== stripos( $p->post_title, 'Nom à renseigner' ) ) {
		$directions[ $etab ] = array( 'nom' => $p->post_title, 'fonction' => (string) sueb_meta( $p->ID, 'fonction' ) );
	}
}

/* Les établissements, dans l'ordre de l'accueil ; ceux qui n'ont pas encore de fiche renvoient au rectorat. */
$ordre  = array( 'FS', 'FSJP', 'FALSH', 'FSEG', 'FMSP', 'ENSET', 'ESTLC', 'ENSTMO', 'ISABEE' );
$fixe   = sueb_etablissements();
$etabs  = array();
foreach ( get_posts( array( 'post_type' => 'ueb_structure', 'post_parent' => 0, 'numberposts' => -1 ) ) as $e ) {
	$sigle           = strtoupper( (string) sueb_meta( $e->ID, 'sigle' ) );
	$etabs[ $sigle ] = array(
		'sigle'     => $sigle ?: $e->post_title,
		'nom'       => $e->post_title,
		'url'       => get_permalink( $e ),
		'couleur'   => (string) sueb_meta( $e->ID, 'couleur' ) ?: ( $fixe[ $sigle ]['couleur'] ?? '#163d2b' ),
		'logo'      => sueb_image_url( $e->ID, 'medium', 'logo' ) ?: sueb_logo( $sigle ),
		'ville'     => (string) sueb_meta( $e->ID, 'ville' ) ?: ( $fixe[ $sigle ]['ville'] ?? '' ),
		'adresse'   => (string) sueb_meta( $e->ID, 'adresse' ),
		'courriel'  => (string) sueb_meta( $e->ID, 'contact_courriel' ),
		'tel'       => (string) sueb_meta( $e->ID, 'contact_telephone' ),
		'direction' => $directions[ $e->ID ] ?? null,
		'position'  => (string) sueb_meta( $e->ID, 'coordonnees' ),
	);
}
foreach ( $fixe as $sigle => $f ) {
	if ( ! isset( $etabs[ $sigle ] ) ) {
		$etabs[ $sigle ] = array( 'sigle' => $sigle, 'nom' => $f['fr'], 'url' => sueb_lien_etablissement( $sigle ), 'couleur' => $f['couleur'], 'logo' => sueb_logo( $sigle ), 'ville' => $f['ville'], 'adresse' => '', 'courriel' => '', 'tel' => '', 'direction' => null, 'position' => '' );
	}
}
uksort( $etabs, function ( $a, $b ) use ( $ordre ) {
	$ra = array_search( $a, $ordre, true );
	$rb = array_search( $b, $ordre, true );
	return array( false === $ra ? 99 : $ra, $a ) <=> array( false === $rb ? 99 : $rb, $b );
} );

/* ---------- Les sites sur la carte ----------
   Chaque lieu (une ville, ou la position précise d'un campus saisie dans la gestion) regroupe le rectorat,
   les services centraux et les établissements qui s'y trouvent, avec leurs contacts. */
$villes = sueb_villes();
$lire_position = function ( $texte, $ville = '' ) use ( $villes ) {
	if ( preg_match( '/(-?\d+(?:[.,]\d+)?)\s*[,;\s]\s*(-?\d+(?:[.,]\d+)?)/', (string) $texte, $m ) ) {
		return array( (float) str_replace( ',', '.', $m[1] ), (float) str_replace( ',', '.', $m[2] ) );
	}
	return $villes[ $ville ] ?? $villes['Ebolowa'];
};
$sites   = array();
$ajouter = function ( array $pos, $lieu, array $entree ) use ( &$sites ) {
	$cle = round( $pos[0], 4 ) . ',' . round( $pos[1], 4 );
	if ( ! isset( $sites[ $cle ] ) ) {
		$sites[ $cle ] = array( 'lieu' => $lieu, 'lat' => $pos[0], 'lon' => $pos[1], 'entrees' => array() );
	}
	$sites[ $cle ]['entrees'][] = $entree;
};
$ville_univ = trim( explode( ',', (string) $u['ville'] )[0] ) ?: 'Ebolowa';
$ajouter( sueb_position_rectorat(), $ville_univ, array( 'type' => 'service', 'titre' => 'Rectorat', 'sous' => 'Administration centrale de l’université', 'adresse' => $u['bp'], 'tel' => $u['tel'], 'courriel' => $u['email'], 'url' => '', 'logo' => sueb_logo(), 'couleur' => '#163d2b' ) );
foreach ( sueb_services_centraux() as $sc ) {
	$ajouter( array( (float) $sc['lat'], (float) $sc['lon'] ), $sc['lieu'] ?: $ville_univ, array( 'type' => 'service', 'titre' => $sc['nom'], 'sous' => 'Service central', 'adresse' => $sc['lieu'], 'tel' => $sc['tel'], 'courriel' => $sc['courriel'], 'url' => '', 'logo' => '', 'couleur' => '#163d2b' ) );
}
foreach ( $etabs as $e ) {
	$ajouter( $lire_position( $e['position'], $e['ville'] ), $e['ville'] ?: $ville_univ, array( 'type' => 'etab', 'titre' => $e['sigle'], 'sous' => $e['nom'], 'adresse' => $e['adresse'], 'tel' => $e['tel'], 'courriel' => $e['courriel'], 'url' => $e['url'], 'logo' => $e['logo'], 'couleur' => $e['couleur'], 'direction' => $e['direction'] ) );
}
/* Les sites les plus fournis d'abord (Ebolowa), puis par nom. */
uasort( $sites, function ( $a, $b ) {
	return array( -count( $a['entrees'] ), $a['lieu'] ) <=> array( -count( $b['entrees'] ), $b['lieu'] );
} );
$sites = array_values( $sites );

/* Leaflet et les fonds OpenStreetMap, sans clé : seulement sur cette page. */
wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', array( 'in_footer' => true ) );
wp_add_inline_script( 'leaflet', file_get_contents( SUEB_DIR . '/assets/js/carte-contact.js' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- fichier du thème

/** Un numéro de téléphone pour un lien tel: (chiffres et +). */
$tel_lien = function ( $tel ) {
	return 'tel:' . preg_replace( '/[^\d+]/', '', $tel );
};

get_header();
?>
<header class="acad-tete contact-tete">
	<div class="conteneur">
		<ol class="fil-ariane"><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li><li aria-current="page">Contact</li></ol>
		<h1 class="acad-tete__titre"><?php echo esc_html( get_the_title() ); ?></h1>
		<?php if ( $intro ) : ?>
			<div class="acad-tete__chapo contact-tete__intro"><?php echo wp_kses_post( apply_filters( 'the_content', $intro ) ); ?></div>
		<?php else : ?>
			<p class="acad-tete__chapo">Une question sur une formation, une inscription ou un partenariat ? Écrivez au rectorat, ou directement à l’établissement concerné.</p>
		<?php endif; ?>
	</div>
</header>

<div class="acad-corps contact-corps">
	<div class="conteneur">
		<section class="contact-rectorat revele" aria-labelledby="titre-rectorat">
			<div class="contact-rectorat__marque">
				<span class="contact-rectorat__logo"><img src="<?php echo esc_url( sueb_logo() ); ?>" alt="" width="72" height="70" loading="lazy"></span>
				<div>
					<p class="contact-rectorat__surtitre">Université d’Ebolowa</p>
					<h2 id="titre-rectorat">Rectorat</h2>
					<?php if ( $recteur ) : ?><p class="contact-rectorat__resp"><?php echo esc_html( $recteur ); ?>, Recteur</p><?php endif; ?>
				</div>
			</div>
			<ul class="contact-liste contact-liste--rectorat">
				<li><?php echo sueb_icone( 'lieu', 20 ); ?><span><?php echo esc_html( $u['bp'] ); ?><br><?php echo esc_html( $u['ville'] ); ?></span></li>
				<?php if ( $u['tel'] ) : ?><li><?php echo sueb_icone( 'telephone', 20 ); ?><a href="<?php echo esc_attr( $tel_lien( $u['tel'] ) ); ?>"><?php echo esc_html( $u['tel'] ); ?></a></li><?php endif; ?>
				<?php if ( $u['email'] ) : ?><li><?php echo sueb_icone( 'courriel', 20 ); ?><a href="mailto:<?php echo esc_attr( $u['email'] ); ?>"><?php echo esc_html( $u['email'] ); ?></a></li><?php endif; ?>
			</ul>
			<div class="contact-rectorat__actions">
				<?php if ( $u['email'] ) : ?><a class="btn btn--or" href="mailto:<?php echo esc_attr( $u['email'] ); ?>"><?php echo sueb_icone( 'courriel', 18 ); ?>Écrire au rectorat</a><?php endif; ?>
				<?php foreach ( array( 'facebook' => 'Facebook', 'youtube' => 'YouTube' ) as $reseau => $nom ) : if ( sueb_lien( $reseau ) ) : ?>
					<a class="contact-reseau" href="<?php echo esc_url( sueb_lien( $reseau ) ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $nom . ' de l’Université d’Ebolowa' ); ?>"><?php echo sueb_icone( $reseau, 20 ); ?></a>
				<?php endif; endforeach; ?>
			</div>
		</section>

		<section class="sites" id="sites" aria-labelledby="titre-sites">
			<header class="tete-section revele">
				<div>
					<p class="sur-titre">Nous trouver</p>
					<h2 class="titre-section" id="titre-sites">Nos sites <span class="titre-section__compte"><?php echo count( $sites ); ?></span></h2>
				</div>
				<p class="tete-section__chapo">Le rectorat, les services centraux et les établissements, ville par ville, avec leurs contacts. Choisissez un site pour le voir sur la carte, ou demandez l’itinéraire.</p>
			</header>
			<div class="sites__grille">
				<div class="sites__carte" id="carte-sites" role="region" aria-label="Carte des sites de l’Université d’Ebolowa" data-sites="<?php echo esc_attr( wp_json_encode( array_map( function ( $s ) {
					return array( 'lieu' => $s['lieu'], 'lat' => $s['lat'], 'lon' => $s['lon'], 'noms' => array_column( $s['entrees'], 'titre' ) );
				}, $sites ) ) ); ?>">
					<p class="sites__repli">Chargement de la carte…</p>
				</div>
				<div class="sites__liste">
					<?php foreach ( $sites as $k => $s ) : $itineraire = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $s['lat'] . ',' . $s['lon'] ); ?>
						<section class="site" id="site-<?php echo (int) $k; ?>" data-site="<?php echo (int) $k; ?>" aria-labelledby="site-<?php echo (int) $k; ?>-titre">
							<header class="site__tete">
								<h3 id="site-<?php echo (int) $k; ?>-titre"><button type="button" data-voir-site="<?php echo (int) $k; ?>"><span class="site__pin" aria-hidden="true"><?php echo (int) ( $k + 1 ); ?></span><?php echo esc_html( $s['lieu'] ); ?></button></h3>
								<a class="site__itineraire" href="<?php echo esc_url( $itineraire ); ?>" target="_blank" rel="noopener"><?php echo sueb_icone( 'lieu', 16 ); ?>Itinéraire</a>
							</header>
							<ul class="site__entrees">
								<?php foreach ( $s['entrees'] as $e ) : $joignable = $e['tel'] || $e['courriel']; ?>
									<li class="entree entree--<?php echo esc_attr( $e['type'] ); ?>" style="--etab:<?php echo esc_attr( $e['couleur'] ); ?>">
										<span class="entree__logo"><?php if ( $e['logo'] ) : ?><img src="<?php echo esc_url( $e['logo'] ); ?>" alt="" width="36" height="36" loading="lazy"><?php else : ?><?php echo sueb_icone( 'personnes', 18 ); ?><?php endif; ?></span>
										<div class="entree__corps">
											<p class="entree__titre"><?php if ( $e['url'] ) : ?><a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['titre'] ); ?></a><?php else : ?><?php echo esc_html( $e['titre'] ); ?><?php endif; ?></p>
											<p class="entree__sous"><?php echo esc_html( $e['sous'] ); ?><?php echo ! empty( $e['direction'] ) ? ' · ' . esc_html( $e['direction']['nom'] ) : ''; ?></p>
											<?php if ( $e['adresse'] && 'service' !== $e['type'] || ( 'service' === $e['type'] && $e['adresse'] && 'Rectorat' === $e['titre'] ) ) : ?><p class="entree__adresse"><?php echo esc_html( $e['adresse'] ); ?></p><?php endif; ?>
											<?php if ( $joignable ) : ?>
												<p class="entree__contacts">
													<?php if ( $e['tel'] ) : ?><a href="<?php echo esc_attr( $tel_lien( $e['tel'] ) ); ?>"><?php echo sueb_icone( 'telephone', 15 ); ?><?php echo esc_html( $e['tel'] ); ?></a><?php endif; ?>
													<?php if ( $e['courriel'] ) : ?><a href="mailto:<?php echo esc_attr( $e['courriel'] ); ?>"><?php echo sueb_icone( 'courriel', 15 ); ?><?php echo esc_html( $e['courriel'] ); ?></a><?php endif; ?>
												</p>
											<?php else : ?>
												<p class="entree__note">Contacts bientôt en ligne : écrivez au <a href="mailto:<?php echo esc_attr( $u['email'] ); ?>">rectorat</a>.</p>
											<?php endif; ?>
										</div>
									</li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	</div>
</div>
<?php
get_footer();
