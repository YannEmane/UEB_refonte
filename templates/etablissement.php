<?php
/**
 * Page d'un établissement : tout l'établissement sur une page. Bandeau à sa
 * couleur (prolongement de sa carte de l'accueil), présentation et direction,
 * départements à dérouler jusqu'aux filières, niveaux et unités d'enseignement,
 * établissements privés sous sa tutelle, équipe, campus, autres établissements.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$p       = $args['page'];
$sigle   = $p['sigle'];
$slug    = strtolower( $sigle );
$texte   = sueb_texte_sur( $p['couleur'] );
/* « la FSJP », « l’ENSET », « l’ISABEE » : élision devant une voyelle. */
$le_sigle = ( preg_match( '/^[AEIOUY]/i', $sigle ) ? 'l’' : ( $p['feminin'] ? 'la ' : 'le ' ) ) . $sigle;
/* L'arborescence part du logo : les départements (ou les cycles) en branches, leurs filières en feuilles. Un
   établissement dont les départements ne sont pas encore connus n'a qu'un groupe « Filières » : chaque filière
   devient alors une branche, et ses niveaux en sont les feuilles. */
$a_departements = (bool) array_filter( $p['departements'], function ( $d ) {
	return ! $d['groupe'];
} );
$par_filiere = ! $a_departements && 1 === count( $p['departements'] );
$rubriques = array_filter( array(
	'presentation' => 'Présentation',
	'formations'   => $a_departements ? 'Départements et filières' : 'Filières',
	'tutelle'      => $p['tutelle'] ? 'Sous sa tutelle' : '',
	'equipe'       => 'Équipe',
	'campus'       => $p['photos'] ? 'Campus' : '',
) );
$chiffres = array_filter( $p['chiffres'] );
?>
<article class="etab" style="--etab:<?php echo esc_attr( $p['couleur'] ); ?>;--sur-etab:<?php echo esc_attr( $texte ); ?>">
	<header class="etab-tete" style="view-transition-name:etab-<?php echo esc_attr( $slug ); ?>" data-etab-tete>
		<div class="conteneur etab-tete__int">
			<div class="etab-tete__texte">
				<ol class="fil-ariane etab-tete__fil">
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li>
					<li><a href="<?php echo esc_url( home_url( '/#etablissements' ) ); ?>">Établissements</a></li>
					<li aria-current="page"><?php echo esc_html( $sigle ); ?></li>
				</ol>
				<span class="etab-tete__logo"><img src="<?php echo esc_url( $p['logo'] ); ?>" alt="" width="72" height="72"></span>
				<p class="etab-tete__sigle"><span class="sr"><?php echo esc_html( $sigle ); ?></span><span class="etab-tete__lettres" aria-hidden="true"><?php foreach ( mb_str_split( $sigle ) as $k => $lettre ) : ?><span style="--k:<?php echo (int) $k; ?>"><?php echo esc_html( $lettre ); ?></span><?php endforeach; ?></span></p>
				<h1 class="etab-tete__nom"><?php echo esc_html( $p['nom'] ); ?></h1>
				<p class="etab-tete__lieu">
					<?php if ( $p['ville'] ) : ?><span><?php echo sueb_icone( 'lieu', 18 ); ?><?php echo esc_html( $p['ville'] ); ?></span><?php endif; ?>
					<span>Université d’Ebolowa</span>
				</p>
				<div class="etab-tete__actions">
					<a class="btn etab-tete__btn" href="#formations">Découvrir les formations<?php echo sueb_icone( 'chevron', 18 ); ?></a>
					<a class="btn btn--verre" href="<?php echo esc_url( SUEB_LIENS['preinscription'] ); ?>">Préinscription en ligne</a>
				</div>
			</div>
			<?php if ( $p['etudiant'] ) : ?>
				<img class="etab-tete__etudiant" src="<?php echo esc_url( $p['etudiant'] ); ?>" alt="" style="view-transition-name:etudiant-<?php echo esc_attr( $slug ); ?>">
			<?php endif; ?>
		</div>
	</header>

	<nav class="etab-nav" aria-label="Sur cette page" data-etab-nav>
		<div class="conteneur">
			<ul>
				<li class="etab-nav__indicateur" aria-hidden="true"></li>
				<?php foreach ( $rubriques as $ancre => $titre ) : ?>
					<li><a href="#<?php echo esc_attr( $ancre ); ?>"><?php echo esc_html( $titre ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</nav>

	<section class="etab-section etab-presentation" id="presentation" aria-labelledby="titre-presentation">
		<div class="conteneur etab-presentation__grille">
			<div class="revele">
				<h2 class="etab-titre" id="titre-presentation">Présentation</h2>
				<div class="etab-presentation__texte">
					<?php if ( $p['presentation'] ) : ?>
						<?php echo wp_kses_post( apply_filters( 'the_content', $p['presentation'] ) ); ?>
					<?php else : ?>
						<p><?php echo esc_html( implode( ' ', sueb_presentation_etablissement( $p ) ) ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( $chiffres ) : ?>
					<dl class="etab-chiffres">
						<?php foreach ( $chiffres as $libelle => $n ) : ?>
							<div><dt><?php echo esc_html( 1 === $n ? preg_replace( array( '/s$/u', '/s /u' ), array( '', ' ' ), $libelle ) : $libelle ); ?></dt><dd data-compteur="<?php echo (int) $n; ?>"><?php echo (int) $n; ?></dd></div>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>
			</div>

			<aside class="etab-direction revele" aria-labelledby="titre-direction">
				<h2 class="etab-titre etab-titre--petit" id="titre-direction">Direction</h2>
				<?php if ( $p['direction'] ) : $d = $p['direction']; ?>
					<a class="etab-direction__carte" href="<?php echo esc_url( $d['url'] ); ?>">
						<span class="profil__portrait etab-direction__portrait"><?php echo sueb_portrait_personnel( $d, 'eager' ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?></span>
						<span class="etab-direction__texte">
							<strong class="<?php echo $d['a_nommer'] ? 'profil__nom--attente' : ''; ?>"><?php echo esc_html( $d['nom'] ); ?></strong>
							<span><?php echo esc_html( $d['fonction'] ); ?></span>
							<span class="etab-direction__lien">Voir la fiche<?php echo sueb_icone( 'droite', 16 ); ?></span>
						</span>
					</a>
				<?php else : ?>
					<p class="vide">La direction sera bientôt présentée.</p>
				<?php endif; ?>
			</aside>
		</div>
	</section>

	<section class="etab-section etab-formations" id="formations" aria-labelledby="titre-formations">
		<div class="conteneur">
			<?php
			/* Chaque filière reçoit un identifiant : sa branche ou sa feuille et la liste A–Z ouvrent la même fiche. */
			$entrees = array();
			foreach ( $p['departements'] as $i => $d ) {
				if ( ! $d['groupe'] ) {
					$entrees[] = array( 'titre' => $d['titre'], 'url' => $d['url'], 'filiere' => '', 'sous' => count( $d['filieres'] ) . ( count( $d['filieres'] ) > 1 ? ' filières' : ' filière' ) );
				}
				foreach ( $d['filieres'] as $k => $f ) {
					$p['departements'][ $i ]['filieres'][ $k ]['cle'] = 'filiere-' . $i . '-' . $k;
					$entrees[] = array( 'titre' => $f['titre'], 'url' => '', 'filiere' => 'filiere-' . $i . '-' . $k, 'sous' => $par_filiere ? implode( ', ', array_keys( $f['niveaux'] ) ) : $d['titre'] );
				}
			}
			usort( $entrees, function ( $a, $b ) {
				return strcoll( remove_accents( $a['titre'] ), remove_accents( $b['titre'] ) );
			} );

			$branches = array();
			if ( $par_filiere ) {
				foreach ( $p['departements'][0]['filieres'] as $f ) {
					$feuilles = array();
					foreach ( array_keys( $f['niveaux'] ) as $n => $niveau ) {
						$ues        = count( $f['niveaux'][ $niveau ] );
						$feuilles[] = array( 'titre' => sueb_nom_niveau( $niveau ), 'detail' => $ues ? $ues . ' UE' : '', 'filiere' => $f['cle'], 'onglet' => $n );
					}
					$nn         = count( $f['niveaux'] );
					$branches[] = array(
						'titre'    => $f['titre'],
						'url'      => '',
						'filiere'  => $f['cle'],
						'sigle'    => '',
						'meta'     => implode( ', ', array_filter( array( $f['diplome'], $nn ? $nn . ( $nn > 1 ? ' niveaux' : ' niveau' ) : '' ) ) ),
						'feuilles' => $feuilles,
						'vide'     => 'Niveaux en cours de publication.',
					);
				}
			} else {
				foreach ( $p['departements'] as $d ) {
					$feuilles = array();
					foreach ( $d['filieres'] as $f ) {
						$feuilles[] = array( 'titre' => $f['titre'], 'detail' => implode( ', ', array_keys( $f['niveaux'] ) ), 'filiere' => $f['cle'], 'onglet' => null );
					}
					$nf         = count( $d['filieres'] );
					$branches[] = array(
						'titre'    => $d['titre'],
						'url'      => $d['url'],
						'filiere'  => '',
						'sigle'    => $d['sigle'],
						'meta'     => ( $nf ? $nf . ( $nf > 1 ? ' filières' : ' filière' ) : 'Aucune filière publiée' ) . ( $d['responsable'] ? ', chef : ' . $d['responsable'] : '' ),
						'feuilles' => $feuilles,
						'vide'     => 'Filières en cours de publication.',
					);
				}
			}
			$offre = (bool) $entrees;
			?>
			<header class="etab-section__tete etab-formations__tete revele">
				<div>
					<h2 class="etab-titre" id="titre-formations"><?php echo $a_departements ? 'Départements et filières' : 'Filières'; ?></h2>
					<?php if ( $a_departements ) : ?>
						<p>Chaque département et ses filières. Choisissez une filière pour voir ses niveaux et ses unités d’enseignement, avec leurs crédits et leur syllabus.</p>
					<?php elseif ( $par_filiere && $offre ) : ?>
						<p>Chaque filière de <?php echo esc_html( $le_sigle ); ?> et ses niveaux. Choisissez un niveau pour voir ses unités d’enseignement, avec leurs crédits et leur syllabus.</p>
					<?php elseif ( $offre ) : ?>
						<p>Les filières de <?php echo esc_html( $le_sigle ); ?>, cycle par cycle. Choisissez une filière pour voir ses niveaux et ses unités d’enseignement.</p>
					<?php endif; ?>
				</div>
				<?php if ( $offre ) : ?>
					<div class="vues" role="group" aria-label="Affichage des filières" data-vues>
						<button type="button" aria-pressed="true" data-vue="arbre"><svg class="icone" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="5" r="2.5"/><path d="M12 7.5V20M12 12H6v4M12 16h6v4"/></svg>Arborescence</button>
						<button type="button" aria-pressed="false" data-vue="az"><svg class="icone" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 18 7.5 6 11 18M5.2 14h4.6M14 6h6l-6 12h6"/></svg>A–Z</button>
					</div>
				<?php endif; ?>
			</header>

			<?php if ( ! $offre ) : ?>
				<div class="etab-vide revele">
					<p><strong>Les départements et filières de <?php echo esc_html( $le_sigle ); ?> sont en cours de publication.</strong></p>
					<p><?php echo $p['tutelle'] ? esc_html( 'En attendant, découvrez plus bas les filières des établissements privés placés sous sa tutelle.' ) : esc_html( 'Pour toute question sur les formations, la préinscription en ligne présente l’offre de chaque établissement.' ); ?></p>
				</div>
			<?php else : ?>
				<div data-vue-panneau="arbre">
					<div class="arbre<?php echo $par_filiere ? ' arbre--filieres' : ''; ?> revele">
						<span class="arbre__sceau"><img src="<?php echo esc_url( $p['logo'] ); ?>" alt="" width="56" height="56" loading="lazy"></span>
						<ol class="arbre__branches">
							<?php foreach ( $branches as $j => $b ) : ?>
								<li class="branche branche--<?php echo 0 === $j % 2 ? 'gauche' : 'droite'; ?>" style="--i:<?php echo (int) $j; ?>;grid-row:<?php echo (int) $j + 1; ?> / span 2">
									<h3 class="branche__titre">
										<?php if ( $b['filiere'] ) : ?>
											<button type="button" data-ouvrir-filiere="<?php echo esc_attr( $b['filiere'] ); ?>"><?php echo esc_html( $b['titre'] ); ?></button>
										<?php else : ?>
											<a href="<?php echo esc_url( $b['url'] ); ?>"><?php echo esc_html( $b['titre'] ); ?><?php echo sueb_icone( 'externe', 14 ); ?></a>
										<?php endif; ?>
									</h3>
									<p class="branche__meta">
										<?php if ( $b['sigle'] ) : ?><span class="branche__sigle"><?php echo esc_html( $b['sigle'] ); ?></span><?php endif; ?>
										<span><?php echo esc_html( $b['meta'] ); ?></span>
									</p>
									<?php if ( $b['feuilles'] ) : ?>
										<ul class="branche__filieres">
											<?php foreach ( $b['feuilles'] as $fe ) : ?>
												<li><button type="button" class="branche__filiere" data-ouvrir-filiere="<?php echo esc_attr( $fe['filiere'] ); ?>"<?php echo null === $fe['onglet'] ? '' : ' data-niveau="' . (int) $fe['onglet'] . '"'; ?>><?php echo esc_html( $fe['titre'] ); ?><?php if ( $fe['detail'] ) : ?><small><?php echo esc_html( $fe['detail'] ); ?></small><?php endif; ?></button></li>
											<?php endforeach; ?>
										</ul>
									<?php else : ?>
										<p class="branche__vide"><?php echo esc_html( $b['vide'] ); ?></p>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ol>
					</div>
				</div>

				<div class="az" data-vue-panneau="az" hidden>
					<?php $lettre = ''; foreach ( $entrees as $en ) : $l = mb_strtoupper( mb_substr( remove_accents( $en['titre'] ), 0, 1 ) ); ?>
						<?php if ( $l !== $lettre ) : ?><?php echo '' === $lettre ? '' : '</ul></div>'; $lettre = $l; ?><div class="az__groupe"><p class="az__lettre" aria-hidden="true"><?php echo esc_html( $l ); ?></p><ul><?php endif; ?>
						<li>
							<?php if ( $en['filiere'] ) : ?>
								<button type="button" data-ouvrir-filiere="<?php echo esc_attr( $en['filiere'] ); ?>"><strong><?php echo esc_html( $en['titre'] ); ?></strong><small><?php echo esc_html( $en['sous'] ); ?></small></button>
							<?php else : ?>
								<a href="<?php echo esc_url( $en['url'] ); ?>"><strong><?php echo esc_html( $en['titre'] ); ?></strong><small><?php echo esc_html( $en['sous'] ); ?></small></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
					<?php echo $entrees ? '</ul></div>' : ''; ?>
				</div>

				<?php foreach ( $p['departements'] as $d ) : foreach ( $d['filieres'] as $f ) : $id_f = $f['cle']; ?>
					<dialog class="fiche-filiere" id="<?php echo esc_attr( $id_f ); ?>" aria-labelledby="<?php echo esc_attr( $id_f . '-titre' ); ?>">
						<div class="fiche-filiere__tete">
							<p class="fiche-filiere__dep"><?php echo esc_html( $par_filiere ? $p['nom'] : $d['titre'] ); ?></p>
							<h3 class="fiche-filiere__titre" id="<?php echo esc_attr( $id_f . '-titre' ); ?>"><?php echo esc_html( $f['titre'] ); ?></h3>
							<p class="filiere__meta">
								<?php if ( $f['diplome'] ) : ?><span class="filiere__diplome"><?php echo esc_html( $f['diplome'] ); ?></span><?php endif; ?>
								<?php if ( $f['credits'] ) : ?><span><?php echo (int) $f['credits']; ?> crédits publiés</span><?php endif; ?>
								<?php if ( $f['responsable'] ) : ?><span>Responsable : <?php echo esc_html( $f['responsable'] ); ?></span><?php endif; ?>
							</p>
							<button class="fiche-filiere__fermer" type="button" data-fermer-filiere aria-label="Fermer la fiche de la filière"><?php echo sueb_icone( 'fermer', 22 ); ?></button>
						</div>
						<div class="fiche-filiere__corps filiere" data-onglets>
							<?php if ( $f['texte'] ) : ?><p class="fiche-filiere__texte"><?php echo esc_html( $f['texte'] ); ?></p><?php endif; ?>
							<?php if ( ! $f['niveaux'] ) : ?>
								<p class="vide">Unités d’enseignement en cours de publication.</p>
							<?php else : ?>
								<div class="fiche-filiere__niveaux">
									<span>Niveaux</span>
									<?php if ( count( $f['niveaux'] ) > 1 ) : ?>
										<div class="filiere__niveaux" role="tablist" aria-label="<?php echo esc_attr( 'Niveaux : ' . $f['titre'] ); ?>">
											<?php $n = 0; foreach ( $f['niveaux'] as $niveau => $ues ) : ?>
												<button type="button" role="tab" id="<?php echo esc_attr( $id_f . '-t' . $n ); ?>" aria-controls="<?php echo esc_attr( $id_f . '-p' . $n ); ?>" aria-selected="<?php echo 0 === $n ? 'true' : 'false'; ?>"<?php echo 0 === $n ? '' : ' tabindex="-1"'; ?>><?php echo esc_html( $niveau ); ?></button>
											<?php $n++; endforeach; ?>
										</div>
									<?php else : ?>
										<span class="filiere__niveau-seul"><?php echo esc_html( array_key_first( $f['niveaux'] ) ); ?></span>
									<?php endif; ?>
								</div>
								<?php $n = 0; foreach ( $f['niveaux'] as $niveau => $ues ) : ?>
									<div class="filiere__panneau" id="<?php echo esc_attr( $id_f . '-p' . $n ); ?>"<?php echo count( $f['niveaux'] ) > 1 ? ' role="tabpanel" aria-labelledby="' . esc_attr( $id_f . '-t' . $n ) . '"' : ''; ?> data-panneau>
										<?php if ( ! $ues ) : ?>
											<p class="fiche-filiere__attente">Les unités d’enseignement de ce niveau seront bientôt publiées, avec leurs crédits et leur syllabus.</p>
										<?php else : ?>
										<table class="ues">
											<caption class="sr"><?php echo esc_html( $f['titre'] . ', ' . $niveau ); ?></caption>
											<thead><tr><th scope="col">Code</th><th scope="col">Unité d’enseignement</th><th scope="col">Semestre</th><th scope="col">Crédits</th><th scope="col">Volume horaire</th><th scope="col"><span class="sr">Syllabus</span></th></tr></thead>
											<tbody>
												<?php foreach ( $ues as $u ) : ?>
													<tr>
														<td class="ues__code"><?php echo esc_html( $u['code'] ); ?></td>
														<th scope="row"><a href="<?php echo esc_url( $u['url'] ); ?>"><?php echo esc_html( $u['titre'] ); ?></a></th>
														<td><?php echo esc_html( $u['semestre'] ); ?></td>
														<td><span class="ues__credits"><?php echo esc_html( $u['credits'] ); ?></span></td>
														<td class="ues__volume"><?php echo esc_html( $u['volume'] ); ?></td>
														<td class="ues__syllabus"><?php if ( $u['syllabus'] ) : ?><a href="<?php echo esc_url( $u['syllabus'] ); ?>" target="_blank" rel="noopener"><?php echo sueb_icone( 'document', 16 ); ?>Syllabus</a><?php else : ?><span>Syllabus à venir</span><?php endif; ?></td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
										<?php endif; ?>
									</div>
								<?php $n++; endforeach; ?>
							<?php endif; ?>
							<a class="lien-fleche fiche-filiere__lien" href="<?php echo esc_url( $f['url'] ); ?>">Page de la filière<?php echo sueb_icone( 'fleche', 18 ); ?></a>
						</div>
					</dialog>
				<?php endforeach; endforeach; ?>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( $p['tutelle'] ) : ?>
		<section class="etab-section etab-tutelle" id="tutelle" aria-labelledby="titre-tutelle">
			<div class="conteneur">
				<header class="etab-section__tete revele">
					<h2 class="etab-titre" id="titre-tutelle">Établissements privés sous sa tutelle</h2>
					<p><?php echo esc_html( ucfirst( $le_sigle ) . ' assure l’encadrement académique de ces établissements : programmes, examens et diplômes des filières suivantes.' ); ?></p>
				</header>
				<ul class="tutelles" data-cascade="logos">
					<?php foreach ( $p['tutelle'] as $t ) : ?>
						<li class="tutelle-carte">
							<span class="tutelle-carte__logo<?php echo $t['logo'] ? ( $t['photo'] ? ' tutelle-carte__logo--photo' : '' ) : ' tutelle-carte__logo--sigle'; ?>"><?php if ( $t['logo'] ) : ?><img src="<?php echo esc_url( $t['logo'] ); ?>" alt="" loading="lazy"><?php else : ?><?php echo esc_html( $t['sigle'] ); ?><?php endif; ?></span>
							<div class="tutelle-carte__texte">
								<h3><a href="<?php echo esc_url( $t['url'] ); ?>"><?php echo esc_html( $t['nom'] ); ?></a></h3>
								<?php if ( $t['ville'] ) : ?><p class="tutelle-carte__ville"><?php echo sueb_icone( 'lieu', 14 ); ?><?php echo esc_html( $t['ville'] ); ?></p><?php endif; ?>
								<?php if ( $t['filieres'] ) : ?>
									<ul class="tutelle-carte__filieres">
										<?php foreach ( $t['filieres'] as $f ) : ?><li><?php echo esc_html( $f ); ?></li><?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<section class="etab-section etab-equipe" id="equipe" aria-labelledby="titre-equipe" data-annuaire>
		<?php
		/* L'équipe, par catégorie (direction, chefs de département, enseignants, personnel d'appui). */
		$groupes = array();
		foreach ( get_terms( array( 'taxonomy' => 'ueb_corps', 'hide_empty' => false, 'orderby' => 'term_id' ) ) as $t ) {
			$groupes[ $t->name ] = array( 'slug' => $t->slug, 'titre' => 'doyens-directeurs' === $t->slug ? 'Direction' : $t->name, 'membres' => array() );
		}
		foreach ( $p['equipe'] as $m ) {
			$cle = isset( $groupes[ $m['corps'] ] ) ? $m['corps'] : 'Autres';
			if ( ! isset( $groupes[ $cle ] ) ) {
				$groupes[ $cle ] = array( 'slug' => 'autres', 'titre' => 'Autres membres', 'membres' => array() );
			}
			$groupes[ $cle ]['membres'][] = $m;
		}
		$groupes = array_filter( $groupes, function ( $g ) {
			return (bool) $g['membres'];
		} );
		$nb_equipe = count( $p['equipe'] );
		?>
		<div class="conteneur">
			<header class="etab-section__tete etab-equipe__tete revele">
				<div>
					<h2 class="etab-titre" id="titre-equipe">L’équipe</h2>
					<p>Direction, chefs de département et enseignants de <?php echo esc_html( $le_sigle ); ?>. Choisissez une personne pour lire son CV.</p>
				</div>
				<?php if ( $nb_equipe > 8 ) : ?>
					<label class="annuaire__recherche etab-equipe__recherche">
						<span class="sr">Rechercher dans l’équipe</span>
						<?php echo sueb_icone( 'recherche', 20 ); ?>
						<input type="search" placeholder="Nom, fonction ou cours" autocomplete="off" data-annuaire-recherche>
					</label>
				<?php endif; ?>
			</header>
			<p class="annuaire__compte" aria-live="polite" data-annuaire-compte></p>
			<?php foreach ( $groupes as $g ) : $n = count( $g['membres'] ); ?>
				<section class="annuaire__groupe" aria-labelledby="equipe-<?php echo esc_attr( $g['slug'] ); ?>" data-groupe="<?php echo esc_attr( $g['slug'] ); ?>">
					<h3 class="annuaire__titre" id="equipe-<?php echo esc_attr( $g['slug'] ); ?>"><?php echo esc_html( $g['titre'] ); ?><small data-groupe-compte><?php echo (int) $n; ?></small></h3>
					<ul class="profils" data-cascade="portraits">
						<?php foreach ( $g['membres'] as $m ) : ?>
							<?php echo sueb_carte_personnel( $m ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>
			<div class="annuaire__vide" data-annuaire-vide hidden>
				<p><strong>Personne ne correspond à cette recherche.</strong> Vérifiez l’orthographe du nom.</p>
				<button class="btn btn--plein" type="button" data-annuaire-effacer>Effacer la recherche</button>
			</div>
			<?php if ( $nb_equipe < 3 ) : ?>
				<p class="etab-equipe__note">Les fiches et les CV des chefs de département et des enseignants seront publiés au fur et à mesure.</p>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( $p['photos'] ) : ?>
		<section class="etab-section etab-campus" id="campus" aria-labelledby="titre-campus">
			<div class="conteneur">
				<header class="etab-section__tete revele">
					<h2 class="etab-titre" id="titre-campus">Campus</h2>
					<?php if ( $p['ville'] ) : ?><p><?php echo esc_html( ucfirst( $le_sigle ) . ' accueille ses étudiants à ' . $p['ville'] . '.' ); ?></p><?php endif; ?>
				</header>
				<ul class="galerie galerie--<?php echo (int) count( $p['photos'] ); ?>" data-cascade="galerie">
					<?php foreach ( $p['photos'] as $ph ) : ?>
						<li><figure><img src="<?php echo esc_url( $ph['src'] ); ?>" alt="<?php echo esc_attr( $ph['legende'] ?: 'Vie étudiante, ' . $sigle ); ?>" loading="lazy"><?php if ( $ph['legende'] ) : ?><figcaption><?php echo esc_html( $ph['legende'] ); ?></figcaption><?php endif; ?></figure></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<nav class="etab-autres" aria-labelledby="titre-autres">
		<div class="conteneur">
			<h2 class="etab-titre etab-titre--petit" id="titre-autres">Les autres établissements</h2>
			<ul data-cascade="logos">
				<?php foreach ( sueb_etablissements() as $s => $e ) : if ( $s === $sigle ) { continue; } ?>
					<li><a href="<?php echo esc_url( sueb_lien_etablissement( $s ) ); ?>" style="--etab:<?php echo esc_attr( $e['couleur'] ); ?>"><span class="etab-autres__logo"><img src="<?php echo esc_url( sueb_logo( $s ) ); ?>" alt="" width="34" height="34" loading="lazy"></span><span><strong><?php echo esc_html( $s ); ?></strong><small><?php echo esc_html( $e['fr'] ); ?></small></span></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</nav>
</article>
