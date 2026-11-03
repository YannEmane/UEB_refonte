<?php
/**
 * Page d'une filière : bandeau photo, accroche et chapô, puis deux colonnes. À gauche,
 * « À propos de la filière » et le programme : une année par volet dépliable (la
 * première ouverte), un tableau par semestre (UE | crédits). À droite, l'encadré de la
 * filière (liens utiles), le contact et les autres filières du département.
 *
 * Tout vient de la gestion (rubrique « Départements, filières et UE ») : intitulé, photo,
 * accroche, résumé, présentation, contact, et les UE avec leurs semestres et crédits.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$id      = (int) $args['id'];
$f       = sueb_filiere( $id );
$racine  = sueb_racine( $id );
$parent  = wp_get_post_parent_id( $id );
$sigle   = (string) sueb_meta( $racine, 'sigle' );
$fixe    = sueb_etablissements()[ strtoupper( $sigle ) ] ?? array();
$couleur = (string) sueb_meta( $racine, 'couleur' ) ?: ( $fixe['couleur'] ?? '#163d2b' );
$groupe  = $parent && sueb_meta( $parent, 'groupe' );

/* Photo : celle de la filière, sinon celle de l'établissement, sinon une photo de son campus. */
$photo = (string) get_the_post_thumbnail_url( $id, 'full' ) ?: (string) get_the_post_thumbnail_url( $racine, 'full' );
if ( ! $photo && ! empty( $fixe['photos'][0] ) ) {
	$photo = sueb_photo( $fixe['photos'][0] );
}
$accroche = trim( (string) sueb_meta( $id, 'accroche' ) );
$chapo    = trim( (string) get_post_field( 'post_excerpt', $id ) );
$apropos  = trim( (string) get_post_field( 'post_content', $id ) );
$courriel = (string) sueb_meta( $id, 'contact_courriel' );
$tel      = (string) sueb_meta( $id, 'contact_telephone' );
$freres   = array_filter( get_children( array( 'post_parent' => $parent, 'post_type' => 'ueb_structure', 'post_status' => 'publish', 'orderby' => 'menu_order title', 'order' => 'ASC' ) ), function ( $p ) use ( $id ) {
	return $p->ID !== $id;
} );

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
$nom_semestre = function ( $s ) {
	return preg_match( '/^S(\d+)$/i', $s, $m ) ? 'Semestre ' . $m[1] : $s;
};
$liens = array_filter( array(
	array( '#programme', 'Programme des cours', (bool) $annees ),
	array( '#a-propos', 'À propos de la filière', true ),
	array( get_permalink( $racine ) . '#equipe', 'L’équipe enseignante de ' . $sigle, true ),
	array( $parent && ! $groupe ? get_permalink( $parent ) : '', 'Page du département', $parent && ! $groupe ),
	array( sueb_lien( 'preinscription' ), 'Préinscription en ligne', (bool) sueb_lien( 'preinscription' ) ),
), function ( $l ) {
	return $l[2];
} );
?>
<article class="programme" style="--etab:<?php echo esc_attr( $couleur ); ?>;--sur-etab:<?php echo esc_attr( sueb_texte_sur( $couleur ) ); ?>">
	<header class="programme-tete"<?php echo $photo ? ' style="--photo:url(' . esc_url( $photo ) . ')"' : ''; ?>>
		<div class="conteneur programme-tete__int">
			<p class="surtitre surtitre--clair">Filière<?php echo $f['code'] ? ' · ' . esc_html( $f['code'] ) : ''; ?><?php echo $f['diplome'] ? ' · ' . esc_html( $f['diplome'] ) : ''; ?></p>
			<h1><?php echo esc_html( $f['titre'] ); ?></h1>
			<ol class="fil-ariane programme-tete__fil">
				<li><a href="<?php echo esc_url( home_url( '/#etablissements' ) ); ?>">Établissements</a></li>
				<?php foreach ( array_reverse( get_post_ancestors( $id ) ) as $a ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $a ) ); ?>"><?php echo esc_html( sueb_meta( $a, 'sigle' ) ?: sueb_titre_et_code( get_the_title( $a ) )[0] ); ?></a></li>
				<?php endforeach; ?>
				<li aria-current="page"><?php echo esc_html( $f['titre'] ); ?></li>
			</ol>
		</div>
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
					<h2 id="titre-programme">Programme des cours<?php echo $f['credits'] ? ' : ' . (int) $f['credits'] . ' crédits' : ''; ?></h2>
					<?php if ( ! $annees ) : ?>
						<p class="vide">Les unités d’enseignement de cette filière seront bientôt publiées.</p>
					<?php else : ?>
						<p class="programme-section__aide">Les unités d’enseignement année par année, avec leurs crédits. Choisissez une UE pour voir son volume horaire et son syllabus.</p>
						<div class="annees">
							<?php $i = 0; foreach ( $annees as $niveau => $an ) : ?>
								<details class="annee"<?php echo 0 === $i ? ' open' : ''; ?>>
									<summary>
										<span class="annee__titre"><?php echo esc_html( sueb_nom_niveau( $niveau ) ); ?></span>
										<span class="annee__resume"><?php echo $an['nb'] ? esc_html( $an['nb'] . ' UE · ' . $an['credits'] . ' crédits' ) : 'À venir'; ?></span>
										<span class="annee__signe" aria-hidden="true"></span>
									</summary>
									<div class="annee__corps">
										<?php if ( ! $an['nb'] ) : ?>
											<p class="vide">Les unités d’enseignement de ce niveau seront bientôt publiées.</p>
										<?php endif; ?>
										<?php foreach ( $an['semestres'] as $sem => $ues ) : ?>
											<table class="semestre">
												<caption class="sr"><?php echo esc_html( sueb_nom_niveau( $niveau ) . ', ' . $nom_semestre( $sem ) ); ?></caption>
												<thead><tr><th scope="col"><?php echo esc_html( $nom_semestre( $sem ) ); ?></th><th scope="col">Crédits</th></tr></thead>
												<tbody>
													<?php foreach ( $ues as $u ) : ?>
														<tr>
															<th scope="row">
																<a href="<?php echo esc_url( $u['url'] ); ?>"><?php if ( $u['code'] ) : ?><span class="semestre__code"><?php echo esc_html( $u['code'] ); ?></span><?php endif; ?><?php echo esc_html( $u['titre'] ); ?></a>
																<?php if ( $u['syllabus'] ) : ?><a class="semestre__syllabus" href="<?php echo esc_url( $u['syllabus'] ); ?>" target="_blank" rel="noopener"><?php echo sueb_icone( 'document', 14 ); ?>Syllabus</a><?php endif; ?>
															</th>
															<td><?php echo $u['credits'] ? esc_html( $u['credits'] . ' crédits' ) : '—'; ?></td>
														</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										<?php endforeach; ?>
									</div>
								</details>
							<?php $i++; endforeach; ?>
						</div>
					<?php endif; ?>
				</section>
			</div>

			<aside class="programme-cote" aria-label="Sur cette filière">
				<nav class="programme-liens" aria-labelledby="titre-liens">
					<h2 id="titre-liens"><?php echo esc_html( $f['titre'] ); ?></h2>
					<ul>
						<?php foreach ( $liens as $l ) : ?>
							<li><a href="<?php echo esc_url( $l[0] ); ?>"<?php echo 0 === strpos( $l[0], 'http' ) && false === strpos( $l[0], home_url() ) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo sueb_icone( 'fleche', 16 ); ?><?php echo esc_html( $l[1] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>

				<section class="programme-contact" aria-labelledby="titre-contact">
					<h2 id="titre-contact">Contact de la filière</h2>
					<p class="programme-contact__etab"><?php echo esc_html( $groupe || ! $parent ? get_the_title( $racine ) : get_the_title( $parent ) ); ?></p>
					<dl>
						<?php if ( $f['responsable'] ) : ?><div><dt>Responsable</dt><dd><?php echo esc_html( $f['responsable'] ); ?></dd></div><?php endif; ?>
						<?php if ( $courriel ) : ?><div><dt>Courriel</dt><dd><a href="mailto:<?php echo esc_attr( $courriel ); ?>"><?php echo esc_html( $courriel ); ?></a></dd></div><?php endif; ?>
						<?php if ( $tel ) : ?><div><dt>Téléphone</dt><dd><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $tel ) ); ?>"><?php echo esc_html( $tel ); ?></a></dd></div><?php endif; ?>
						<div><dt>Établissement</dt><dd><a href="<?php echo esc_url( get_permalink( $racine ) ); ?>"><?php echo esc_html( $sigle ?: get_the_title( $racine ) ); ?></a><?php echo ! empty( $fixe['ville'] ) ? ', ' . esc_html( $fixe['ville'] ) : ''; ?></dd></div>
					</dl>
				</section>

				<?php if ( $freres ) : ?>
					<nav class="programme-freres" aria-labelledby="titre-freres">
						<h2 id="titre-freres"><?php echo $groupe ? 'Les autres filières' : 'Dans le même département'; ?></h2>
						<ul>
							<?php foreach ( $freres as $p ) : ?>
								<li><a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( sueb_titre_et_code( $p->post_title )[0] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</nav>
				<?php endif; ?>
			</aside>
		</div>
	</div>
</article>
