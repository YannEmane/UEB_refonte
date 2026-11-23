<?php
/**
 * Page d'une filière : bandeau photo, accroche et chapô, puis sur toute la largeur
 * « À propos de la filière », le programme et l'équipe pédagogique. Le programme se lit
 * comme une maquette pédagogique : un niveau par onglet, puis un tableau par semestre, que l'on déroule
 * ou enroule (le premier de chaque niveau est ouvert)
 * (code, unité, enseignants, crédits, heures de CM, TD et TP, total) avec la ligne
 * des totaux du semestre. La colonne de droite (liens
 * utiles, contact de la filière, autres filières du département) a été retirée le
 * 2026-11-23 à la demande de l'utilisateur.
 *
 * Tout vient de la gestion (rubrique « Départements, filières et UE ») : intitulé, photo,
 * accroche, résumé, présentation, et les UE avec leurs semestres et crédits.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$id      = (int) $args['id'];
$f       = sueb_filiere( $id );
$racine  = sueb_racine( $id );
$sigle   = (string) sueb_meta( $racine, 'sigle' );
$fixe    = sueb_etablissements()[ strtoupper( $sigle ) ] ?? array();
$couleur = (string) sueb_meta( $racine, 'couleur' ) ?: ( $fixe['couleur'] ?? '#163d2b' );

/* Photo : celle de la filière (gestion), sinon celle de son domaine avec son crédit (inc/photos-filieres.php),
   sinon celle de l'établissement, sinon une photo de son campus. */
$credit = null;
$photo  = (string) get_the_post_thumbnail_url( $id, 'full' );
if ( ! $photo && ( $du_domaine = sueb_photo_filiere( $id ) ) ) {
	$photo  = $du_domaine['url'];
	$credit = $du_domaine;
}
$photo = $photo ?: (string) get_the_post_thumbnail_url( $racine, 'full' );
if ( ! $photo && ! empty( $fixe['photos'][0] ) ) {
	$photo = sueb_photo( $fixe['photos'][0] );
}
$accroche = trim( (string) sueb_meta( $id, 'accroche' ) );
$chapo    = trim( (string) get_post_field( 'post_excerpt', $id ) );
$apropos  = trim( (string) get_post_field( 'post_content', $id ) );

/* Les UE de chaque année, rangées par semestre. */
$annees = array();
foreach ( $f['niveaux'] as $niveau => $ues ) {
	$semestres = array();
	foreach ( $ues as $u ) {
		$semestres[ $u['semestre'] ?: 'Autres' ][] = $u;
	}
	ksort( $semestres, SORT_NATURAL );
	$annees[ $niveau ] = array(
		'semestres' => $semestres,
		'credits'   => array_sum( array_map( 'intval', array_column( $ues, 'credits' ) ) ),
		'nb'        => count( $ues ),
	);
}
/* L'équipe pédagogique : les enseignants de toutes ses UE, une fois chacun. */
$profs = array();
foreach ( $f['niveaux'] as $ues ) {
	foreach ( $ues as $u ) {
		foreach ( $u['profs'] as $prof ) {
			$profs[ $prof['id'] ] = $prof;
		}
	}
}
/* La colonne « Syllabus » n'apparaît que si au moins une UE de la filière en a un. */
$avec_syllabus = false;
foreach ( $f['niveaux'] as $ues ) {
	$avec_syllabus = $avec_syllabus || (bool) array_filter( array_column( $ues, 'syllabus' ) );
}
$nom_semestre = function ( $s ) {
	return preg_match( '/^S(\d+)$/i', $s, $m ) ? 'Semestre ' . $m[1] : $s;
};
?>
<article class="programme" style="--etab:<?php echo esc_attr( $couleur ); ?>;--sur-etab:<?php echo esc_attr( sueb_texte_sur( $couleur ) ); ?>">
	<header class="programme-tete"<?php echo $photo ? ' style="--photo:url(' . esc_url( $photo ) . ')"' : ''; ?>>
		<div class="conteneur programme-tete__int">
			<p class="surtitre surtitre--clair">Filière<?php echo $f['code'] ? ' · ' . esc_html( $f['code'] ) : ''; ?><?php echo $f['diplome'] ? ' · ' . esc_html( $f['diplome'] ) : ''; ?></p>
			<h1><?php echo esc_html( $f['titre'] ); ?></h1>
			<ol class="fil-ariane programme-tete__fil">
				<li><a href="<?php echo esc_url( sueb_url_academie() ); ?>">Académie</a></li>
				<?php foreach ( array_reverse( get_post_ancestors( $id ) ) as $a ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $a ) ); ?>"><?php echo esc_html( sueb_meta( $a, 'sigle' ) ?: sueb_titre_et_code( get_the_title( $a ) )[0] ); ?></a></li>
				<?php endforeach; ?>
				<li aria-current="page"><?php echo esc_html( $f['titre'] ); ?></li>
			</ol>
		</div>
		<?php if ( $credit ) : ?>
			<p class="programme-tete__credit"><a href="<?php echo esc_url( $credit['source'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $credit['credit'] ); ?></a></p>
		<?php endif; ?>
	</header>

	<div class="conteneur">
		<?php if ( $accroche || $chapo ) : ?>
			<div class="programme-intro">
				<?php if ( $accroche ) : ?><p class="programme-intro__accroche"><?php echo esc_html( $accroche ); ?></p><?php endif; ?>
				<?php if ( $chapo ) : ?><p class="programme-intro__chapo"><?php echo esc_html( $chapo ); ?></p><?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="programme-corps">
			<div class="programme-principal">
				<section class="programme-section" id="a-propos" aria-labelledby="titre-a-propos">
					<h2 id="titre-a-propos">À propos de la filière</h2>
					<?php if ( $apropos ) : ?>
						<div class="prose programme-prose"><?php echo wp_kses_post( apply_filters( 'the_content', $apropos ) ); ?></div>
					<?php else : ?>
						<p class="vide">La présentation de cette filière sera bientôt publiée.</p>
					<?php endif; ?>
				</section>

				<section class="programme-section" id="programme" aria-labelledby="titre-programme">
					<h2 id="titre-programme">Programme des cours</h2>
					<?php if ( ! array_sum( array_column( $annees, 'nb' ) ) ) : ?>
						<p class="vide">Les unités d’enseignement de cette filière seront bientôt publiées.</p>
						<?php if ( $annees ) : ?><p class="programme-section__aide"><?php echo esc_html( ( count( $annees ) > 1 ? 'Niveaux ouverts : ' : 'Niveau ouvert : ' ) . implode( ', ', array_map( 'sueb_nom_niveau', array_map( 'strval', array_keys( $annees ) ) ) ) . '.' ); ?></p><?php endif; ?>
					<?php else : ?>
						<?php
						$nb_ue      = array_sum( array_column( $annees, 'nb' ) );
						$nb_credits = array_sum( array_column( $annees, 'credits' ) );
						$plusieurs  = count( $annees ) > 1;
						?>
						<p class="programme-section__aide">
							<?php
							echo esc_html( sprintf(
								'%s, %d unité%s d’enseignement%s. %sPour chaque semestre, le tableau donne les crédits de chaque unité et ses heures de cours magistraux (CM), de travaux dirigés (TD) et de travaux pratiques (TP).',
								$plusieurs ? count( $annees ) . ' niveaux' : sueb_nom_niveau( (string) array_key_first( $annees ) ),
								$nb_ue,
								$nb_ue > 1 ? 's' : '',
								$nb_credits ? ', ' . $nb_credits . ' crédits en tout' : '',
								$plusieurs ? 'Choisissez un niveau. ' : ''
							) );
							?>
						</p>
						<div class="maquette"<?php echo $plusieurs ? ' data-onglets' : ''; ?>>
							<?php if ( $plusieurs ) : ?>
								<div class="maquette__onglets" role="tablist" aria-label="Niveaux de la filière">
									<?php $k = 0; foreach ( $annees as $niveau => $an ) : ?>
										<button type="button" role="tab" id="onglet-annee-<?php echo (int) $k; ?>" aria-controls="annee-<?php echo (int) $k; ?>" aria-selected="<?php echo 0 === $k ? 'true' : 'false'; ?>"<?php echo 0 === $k ? '' : ' tabindex="-1"'; ?>>
											<span class="maquette__annee"><?php echo esc_html( sueb_nom_niveau( $niveau ) ); ?></span>
											<span class="maquette__resume"><?php echo $an['nb'] ? esc_html( $an['nb'] . ' UE, ' . $an['credits'] . ' crédits' ) : 'À venir'; ?></span>
										</button>
									<?php $k++; endforeach; ?>
								</div>
							<?php endif; ?>

							<?php $k = 0; foreach ( $annees as $niveau => $an ) : ?>
								<div class="maquette__panneau" id="annee-<?php echo (int) $k; ?>"<?php echo $plusieurs ? ' role="tabpanel" aria-labelledby="onglet-annee-' . (int) $k . '" tabindex="0" data-panneau' : ''; ?>>
									<?php if ( $plusieurs ) : ?><h3 class="sr"><?php echo esc_html( sueb_nom_niveau( $niveau ) ); ?></h3><?php endif; ?>
									<?php if ( ! $an['nb'] ) : ?>
										<p class="vide">Les unités d’enseignement de ce niveau seront bientôt publiées.</p>
									<?php endif; ?>
									<?php $j = 0; foreach ( $an['semestres'] as $sem => $ues ) :
										/* Les heures de chaque UE en nombres, et les totaux du semestre. */
										$lignes = array();
										$tot    = array( 'credits' => 0, 'CM' => 0, 'TD' => 0, 'TP' => 0, 'total' => 0 );
										foreach ( $ues as $u ) {
											$v        = sueb_volume_ue( $u['volume'] );
											$lignes[] = array( $u, $v );
											$tot['credits'] += (int) $u['credits'];
											foreach ( array( 'CM', 'TD', 'TP', 'total' ) as $h ) {
												$tot[ $h ] += (float) $v[ $h ];
											}
										}
										$titre_sem = 'Autres' === $sem ? 'Autres unités d’enseignement' : $nom_semestre( $sem );
										$premier   = 0 === $j; // le premier semestre de chaque niveau est ouvert, les autres enroulés
										$id_sem    = 'semestre-' . $k . '-' . $j++;
										/* L'onglet du semestre : son numéro (03 pour S3) et une couleur par numéro, sur cinq. */
										$num_sem   = preg_match( '/(\d+)/', (string) $sem, $m_sem ) ? (int) $m_sem[1] : 0;
										$teinte    = $num_sem ? ( $num_sem - 1 ) % 5 + 1 : 1;
										?>
										<details class="maquette__semestre maquette__semestre--t<?php echo (int) $teinte; ?>"<?php echo $premier ? ' open' : ''; ?>>
											<summary class="maquette__bascule">
												<span class="maquette__num" aria-hidden="true"><?php echo esc_html( $num_sem ? str_pad( (string) $num_sem, 2, '0', STR_PAD_LEFT ) : 'UE' ); ?></span>
												<?php echo $plusieurs ? '<h4' : '<h3'; ?> class="maquette__titre" id="<?php echo esc_attr( $id_sem ); ?>"><?php echo esc_html( $titre_sem ); ?> <span><?php echo esc_html( count( $ues ) . ' UE' . ( $tot['credits'] ? ', ' . $tot['credits'] . ' crédits' : '' ) ); ?></span><?php echo $plusieurs ? '</h4>' : '</h3>'; ?>
												<span class="maquette__signe" aria-hidden="true"></span>
											</summary>
											<div class="maquette__contenu">
											<table class="maquette__table" aria-labelledby="<?php echo esc_attr( $id_sem ); ?>">
												<thead>
													<tr>
														<th scope="col" rowspan="2" class="maquette__code">Code</th>
														<th scope="col" rowspan="2">Unité d’enseignement</th>
														<th scope="col" rowspan="2" class="maquette__enseignants">Enseignants</th>
														<th scope="col" rowspan="2" class="maquette__nombre">Crédits</th>
														<th scope="colgroup" colspan="4" class="maquette__groupe">Volume horaire (heures)</th>
														<?php if ( $avec_syllabus ) : ?><th scope="col" rowspan="2">Syllabus</th><?php endif; ?>
													</tr>
													<tr>
														<th scope="col" class="maquette__nombre"><abbr title="Cours magistraux">CM</abbr></th>
														<th scope="col" class="maquette__nombre"><abbr title="Travaux dirigés">TD</abbr></th>
														<th scope="col" class="maquette__nombre"><abbr title="Travaux pratiques">TP</abbr></th>
														<th scope="col" class="maquette__nombre">Total</th>
													</tr>
												</thead>
												<tbody>
													<?php foreach ( $lignes as list( $u, $v ) ) : ?>
														<tr>
															<td class="maquette__code"><?php echo esc_html( $u['code'] ); ?></td>
															<th scope="row" class="maquette__ue">
																<a href="<?php echo esc_url( $u['url'] ); ?>"><?php echo esc_html( $u['titre'] ); ?></a>
															</th>
															<td class="maquette__enseignants" data-libelle="Enseignants">
																<?php if ( $u['profs'] ) : ?>
																	<span class="maquette__profs">
																		<?php foreach ( $u['profs'] as $prof ) : ?>
																			<a class="prof" href="<?php echo esc_url( $prof['url'] ); ?>"><span class="avatar avatar--petit"><?php echo sueb_portrait_personnel( $prof ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?></span><span class="prof__nom"><?php echo esc_html( $prof['nom'] ); ?></span></a>
																		<?php endforeach; ?>
																	</span>
																<?php else : ?>
																	<span class="maquette__vide">À préciser</span>
																<?php endif; ?>
															</td>
															<td class="maquette__nombre maquette__credits"><?php echo esc_html( $u['credits'] ); ?></td>
															<?php if ( '' !== $v['texte'] ) : ?>
																<td class="maquette__duree" colspan="4"><?php echo esc_html( $v['texte'] ); ?></td>
															<?php else : ?>
																<td class="maquette__nombre" data-libelle="CM"><?php echo esc_html( sueb_nombre( $v['CM'] ) ); ?></td>
																<td class="maquette__nombre" data-libelle="TD"><?php echo esc_html( sueb_nombre( $v['TD'] ) ); ?></td>
																<td class="maquette__nombre" data-libelle="TP"><?php echo esc_html( sueb_nombre( $v['TP'] ) ); ?></td>
																<td class="maquette__nombre maquette__total" data-libelle="Total"><?php echo null === $v['total'] ? '' : esc_html( sueb_nombre( $v['total'] ) . ' h' ); ?></td>
															<?php endif; ?>
															<?php if ( $avec_syllabus ) : ?>
																<td class="maquette__syllabus"><?php if ( $u['syllabus'] ) : ?><a href="<?php echo esc_url( $u['syllabus'] ); ?>" target="_blank" rel="noopener"><?php echo sueb_icone( 'document', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG du thème ?>Syllabus</a><?php endif; ?></td>
															<?php endif; ?>
														</tr>
													<?php endforeach; ?>
												</tbody>
												<?php if ( count( $ues ) > 1 ) : ?>
													<tfoot>
														<tr>
															<th scope="row" colspan="3">Total du semestre</th>
															<td class="maquette__nombre maquette__credits"><?php echo esc_html( $tot['credits'] ); ?></td>
															<td class="maquette__nombre" data-libelle="CM"><?php echo esc_html( sueb_nombre( $tot['CM'] ) ); ?></td>
															<td class="maquette__nombre" data-libelle="TD"><?php echo esc_html( sueb_nombre( $tot['TD'] ) ); ?></td>
															<td class="maquette__nombre" data-libelle="TP"><?php echo esc_html( sueb_nombre( $tot['TP'] ) ); ?></td>
															<td class="maquette__nombre maquette__total" data-libelle="Total"><?php echo esc_html( sueb_nombre( $tot['total'] ) . ' h' ); ?></td>
															<?php if ( $avec_syllabus ) : ?><td></td><?php endif; ?>
														</tr>
													</tfoot>
												<?php endif; ?>
											</table>
											</div>
										</details>
									<?php endforeach; ?>
								</div>
							<?php $k++; endforeach; ?>
						</div>
					<?php endif; ?>
				</section>

				<?php if ( $profs ) : ?>
					<section class="programme-section programme-profs" aria-labelledby="titre-profs">
						<h2 id="titre-profs">Équipe pédagogique</h2>
						<ul>
							<?php foreach ( $profs as $prof ) : ?>
								<li><a class="prof" href="<?php echo esc_url( $prof['url'] ); ?>"><span class="avatar"><?php echo sueb_portrait_personnel( $prof ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?></span><span><strong class="prof__nom"><?php echo esc_html( $prof['nom'] ); ?></strong><small><?php echo esc_html( $prof['grade'] ?: $prof['fonction'] ); ?></small></span></a></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>
			</div>

		</div>
	</div>
</article>
