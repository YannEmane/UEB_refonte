<?php
/**
 * Partenariats et conventions : à gauche le propos et les chiffres, à droite
 * une carte du Cameroun où, depuis Ebolowa, un arc relie l'UEb à chaque ville
 * partenaire ; dessous, les médaillons des partenaires défilent sans fin, de
 * droite à gauche. Survoler un médaillon situe le partenaire sur la carte, et
 * les familles (enseignement supérieur, État et recherche, entreprises) filtrent
 * la carte et le défilé (site.js).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$reseau   = sueb_reseau_partenaires();
$liste    = $reseau['partenaires'];
$familles = $reseau['familles'];
$villes   = sueb_villes();
$carte    = sueb_carte_cameroun();
$hub      = sueb_point_carte( $villes['Ebolowa'][0], $villes['Ebolowa'][1] );

/* Les conventions datées d'abord, de la plus récente à la plus ancienne ; puis les universités et le ministère. */
usort( $liste, function ( $a, $b ) {
	return array( '' === $a['date'], $b['date'] ) <=> array( '' === $b['date'], $a['date'] );
} );

/* Les villes de la carte et leurs partenaires. */
$sur_carte = array();
foreach ( $liste as $k => $p ) {
	if ( $p['ville'] && isset( $villes[ $p['ville'] ] ) && 'Ebolowa' !== $p['ville'] ) {
		$sur_carte[ $p['ville'] ][] = $k;
	}
}
$comptes = array_count_values( array_column( $liste, 'famille' ) );
$dates   = array_filter( array_column( $liste, 'date' ) );
/* Place du nom de la ville quand il gênerait à droite du point. */
$etiquettes = array( 'Yaoundé' => 'haut', 'Kribi' => 'gauche', 'Douala' => 'gauche', 'Buea' => 'gauche' );

/** Arc de Ebolowa à la ville : une courbe qui s'élève toujours du même côté. */
$arc = function ( $x, $y ) use ( $hub ) {
	$mx = ( $hub[0] + $x ) / 2;
	$my = ( $hub[1] + $y ) / 2;
	$dx = $x - $hub[0];
	$dy = $y - $hub[1];
	$cx = $mx + $dy * .28;
	$cy = $my - $dx * .28;
	return sprintf( 'M%.1f %.1fQ%.1f %.1f %.1f %.1f', $hub[0], $hub[1], $cx, $cy, $x, $y );
};
$slug = function ( $v ) {
	return sanitize_title( $v );
};
?>
<section class="section partenariats" id="partenariats" aria-labelledby="titre-part" data-reseau>
	<div class="conteneur">
		<div class="reseau">
			<div class="reseau__texte revele">
				<header class="section__tete reseau__tete">
					<p class="surtitre">Partenariats et conventions</p>
					<h2 id="titre-part">Ils avancent avec l’UEb</h2>
					<p class="section__chapo">Universités publiques, ministères, centres de recherche, entreprises et établissements privés placés sous sa tutelle académique : depuis Ebolowa, l’UEb tisse des conventions partout au Cameroun pour la recherche, les formations et l’insertion de ses diplômés.</p>
				</header>
				<div class="reseau__chiffres">
					<p><strong data-compteur="<?php echo (int) count( $liste ); ?>"><?php echo (int) count( $liste ); ?></strong>partenaires</p>
					<p><strong data-compteur="<?php echo (int) count( $sur_carte ); ?>"><?php echo (int) count( $sur_carte ); ?></strong>villes reliées à Ebolowa</p>
					<?php if ( $dates ) : ?><p><strong data-compteur="<?php echo (int) count( $dates ); ?>"><?php echo (int) count( $dates ); ?></strong>conventions signées depuis <?php echo esc_html( substr( min( $dates ), 0, 4 ) ); ?></p><?php endif; ?>
				</div>
				<div class="reseau__familles" role="group" aria-label="Familles de partenaires" data-reseau-filtres>
					<button type="button" aria-pressed="true" data-famille="">Tous les partenaires<small><?php echo (int) count( $liste ); ?></small></button>
					<?php foreach ( $familles as $f => $nom ) : if ( empty( $comptes[ $f ] ) ) { continue; } ?>
						<button type="button" aria-pressed="false" data-famille="<?php echo esc_attr( $f ); ?>"><span class="reseau__pastille reseau__pastille--<?php echo esc_attr( $f ); ?>" aria-hidden="true"></span><?php echo esc_html( $nom ); ?><small><?php echo (int) $comptes[ $f ]; ?></small></button>
					<?php endforeach; ?>
				</div>
			</div>

			<figure class="carte revele" data-carte>
				<svg class="carte__svg" viewBox="0 0 <?php echo esc_attr( $carte['largeur'] . ' ' . $carte['hauteur'] ); ?>" role="img" aria-labelledby="carte-titre carte-desc">
					<title id="carte-titre">Carte des partenaires de l’UEb au Cameroun</title>
					<desc id="carte-desc"><?php
						$phrases = array();
						foreach ( $sur_carte as $v => $ks ) {
							$phrases[] = $v . ' (' . count( $ks ) . ')';
						}
						echo esc_html( 'Depuis Ebolowa, des partenaires à ' . implode( ', ', $phrases ) . '.' );
					?></desc>
					<clipPath id="carte-cadre"><rect width="<?php echo esc_attr( $carte['largeur'] ); ?>" height="<?php echo esc_attr( $carte['hauteur'] ); ?>"/></clipPath>
					<path class="carte__voisins" d="<?php echo esc_attr( $carte['voisins'] ); ?>" clip-path="url(#carte-cadre)"/>
					<path class="carte__pays" d="<?php echo esc_attr( $carte['pays'] ); ?>" pathLength="1"/>
					<g class="carte__arcs">
						<?php $i = 0; foreach ( $sur_carte as $v => $ks ) : $pt = sueb_point_carte( $villes[ $v ][0], $villes[ $v ][1] ); ?>
							<path id="arc-<?php echo esc_attr( $slug( $v ) ); ?>" class="carte__arc" d="<?php echo esc_attr( $arc( $pt[0], $pt[1] ) ); ?>" pathLength="1" style="--i:<?php echo (int) $i++; ?>" data-ville="<?php echo esc_attr( $v ); ?>"/>
						<?php endforeach; ?>
					</g>
					<g class="carte__flux" aria-hidden="true">
						<?php $i = 0; foreach ( $sur_carte as $v => $ks ) : $duree = 2.4 + ( $i % 3 ) * .35; ?>
							<?php foreach ( array( 0, .5 ) as $decalage ) : ?>
								<circle class="carte__particule" r="2.6" data-ville="<?php echo esc_attr( $v ); ?>">
									<animateMotion dur="<?php echo esc_attr( $duree ); ?>s" begin="<?php echo esc_attr( round( $i * .3 + $decalage * $duree, 2 ) ); ?>s" repeatCount="indefinite"><mpath href="#arc-<?php echo esc_attr( $slug( $v ) ); ?>"/></animateMotion>
									<animate attributeName="opacity" values="0;1;1;0" keyTimes="0;.12;.82;1" dur="<?php echo esc_attr( $duree ); ?>s" begin="<?php echo esc_attr( round( $i * .3 + $decalage * $duree, 2 ) ); ?>s" repeatCount="indefinite"/>
								</circle>
							<?php endforeach; ?>
						<?php $i++; endforeach; ?>
					</g>
					<g class="carte__villes">
						<?php $i = 0; foreach ( $sur_carte as $v => $ks ) : $pt = sueb_point_carte( $villes[ $v ][0], $villes[ $v ][1] ); $place = $etiquettes[ $v ] ?? 'droite'; ?>
							<g transform="translate(<?php echo esc_attr( $pt[0] . ' ' . $pt[1] ); ?>)">
								<g class="carte__ville" style="--i:<?php echo (int) $i++; ?>" data-ville="<?php echo esc_attr( $v ); ?>">
									<circle class="carte__onde" r="6"/>
									<circle class="carte__point" r="4.6"/>
									<text class="carte__nom" x="<?php echo 'gauche' === $place ? -10 : ( 'haut' === $place ? 0 : 10 ); ?>" y="<?php echo 'haut' === $place ? -12 : 4; ?>"<?php echo 'droite' === $place ? '' : ( 'gauche' === $place ? ' text-anchor="end"' : ' text-anchor="middle"' ); ?>><?php echo esc_html( $v ); ?><?php if ( count( $ks ) > 1 ) : ?><tspan class="carte__nombre" dx="5" data-nombre><?php echo (int) count( $ks ); ?></tspan><?php endif; ?></text>
								</g>
							</g>
						<?php endforeach; ?>
					</g>
					<g transform="translate(<?php echo esc_attr( $hub[0] . ' ' . $hub[1] ); ?>)">
						<g class="carte__hub">
							<circle class="carte__onde carte__onde--hub" r="9"/>
							<circle class="carte__onde carte__onde--hub carte__onde--retard" r="9"/>
							<circle class="carte__coeur" r="7.5"/>
							<text class="carte__nom carte__nom--hub" y="24" text-anchor="middle">Ebolowa</text>
						</g>
					</g>
				</svg>
			</figure>

		</div>
	</div>

	<div class="defile revele" data-defile>
		<div class="conteneur defile__tete">
			<p class="defile__aide">Survolez un partenaire pour le situer sur la carte.</p>
			<button class="defile__pause" type="button" data-defile-pause aria-label="Mettre le défilement en pause">
				<svg class="icone" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z" fill="currentColor"/></svg>
			</button>
		</div>
		<div class="defile__fenetre">
			<ul class="defile__piste" style="--duree:<?php echo (int) max( 30, count( $liste ) * 3.4 ); ?>s" aria-label="Partenaires de l’UEb">
				<?php foreach ( array( false, true ) as $copie ) : /* deux fois la même suite : la boucle ne se voit pas */ ?>
					<?php foreach ( $liste as $p ) : $balise = $p['url'] ? 'a' : 'span'; ?>
						<li class="medaillon" data-famille="<?php echo esc_attr( $p['famille'] ); ?>" data-ville="<?php echo esc_attr( $p['ville'] ); ?>"<?php echo $copie ? ' aria-hidden="true"' : ''; ?>>
							<<?php echo $balise; ?> class="medaillon__lien"<?php echo $p['url'] ? ' href="' . esc_url( $p['url'] ) . '"' . ( $copie ? ' tabindex="-1"' : '' ) : ''; ?>>
								<span class="medaillon__disque medaillon__disque--<?php echo esc_attr( $p['famille'] ); ?><?php echo $p['logo'] ? ( $p['photo'] ? ' medaillon__disque--photo' : '' ) : ' medaillon__disque--sigle'; ?><?php echo ! $p['logo'] && mb_strlen( $p['sigle'] ) > 5 ? ' medaillon__disque--long' : ''; ?>">
									<?php if ( $p['logo'] ) : ?><img src="<?php echo esc_url( $p['logo'] ); ?>" alt="" loading="lazy" decoding="async"><?php else : ?><?php echo esc_html( $p['sigle'] ); ?><?php endif; ?>
								</span>
								<span class="medaillon__nom" title="<?php echo esc_attr( $p['nom'] ); ?>"><?php echo esc_html( $p['nom'] ); ?></span>
								<?php if ( $p['tutelle'] ) : ?><span class="medaillon__tutelle">Tutelle <?php echo esc_html( $p['tutelle'] ); ?></span><?php endif; ?>
								<?php if ( $p['ville'] ) : ?><span class="medaillon__ville"><?php echo sueb_icone( 'lieu', 14 ); ?><?php echo esc_html( $p['ville'] ); ?></span><?php endif; ?>
							</<?php echo $balise; ?>>
						</li>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>
