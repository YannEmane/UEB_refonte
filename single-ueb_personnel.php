<?php
/**
 * Fiche d'un membre du personnel, présentée comme un CV : portrait, fonction,
 * grade, établissement et département ; domaines de recherche, parcours,
 * formation, enseignements, publications et biographie ; CV complet en PDF.
 * Puis d'autres membres du même établissement (ou de la même catégorie).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();

$m        = sueb_membre_personnel( get_post() );
$etab_url = $m['etab_id'] ? get_permalink( $m['etab_id'] ) : '';
/* Ses enseignements : les UE où il figure parmi les enseignants, par établissement. */
$cours    = sueb_ue_enseignees( $m['id'] );
$nb_cours = array_sum( array_map( function ( $e ) {
	return count( $e['ues'] );
}, $cours ) );

/* Voir aussi : le même établissement d'abord, puis la même catégorie. */
$voisins = array();
if ( $m['etab_id'] ) {
	$voisins = get_posts( array( 'post_type' => 'ueb_personnel', 'numberposts' => 4, 'exclude' => array( $m['id'] ), 'meta_key' => '_sueb_etablissement', 'meta_value' => $m['etab_id'], 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
}
$termes = get_the_terms( $m['id'], 'ueb_corps' );
if ( count( $voisins ) < 4 && $termes && ! is_wp_error( $termes ) ) {
	$voisins = array_merge( $voisins, get_posts( array( 'post_type' => 'ueb_personnel', 'numberposts' => 4 - count( $voisins ), 'exclude' => array_merge( array( $m['id'] ), wp_list_pluck( $voisins, 'ID' ) ), 'tax_query' => array( array( 'taxonomy' => 'ueb_corps', 'terms' => $termes[0]->term_id ) ), 'orderby' => 'menu_order title', 'order' => 'ASC' ) ) );
}
?>
<header class="page-tete fiche-tete" style="--etab:<?php echo esc_attr( $m['couleur'] ); ?>;--sur-etab:<?php echo esc_attr( sueb_texte_sur( $m['couleur'] ) ); ?>">
	<div class="conteneur">
		<ol class="fil-ariane">
			<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li>
			<li><a href="<?php echo esc_url( home_url( '/#personnel' ) ); ?>">Personnel</a></li>
			<?php if ( $m['corps'] ) : ?><li><?php echo esc_html( $m['corps'] ); ?></li><?php endif; ?>
		</ol>
		<div class="fiche-tete__int">
		<div class="fiche-tete__portrait profil__portrait">
			<?php echo sueb_portrait_personnel( $m, 'eager' ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?>
		</div>
		<div class="fiche-tete__texte" data-cv-tete>
			<p class="fiche-tete__etab"><img src="<?php echo esc_url( sueb_logo( $m['etab'] ) ); ?>" alt="" width="28" height="28"><?php echo esc_html( $m['etab_nom'] ); ?></p>
			<h1<?php echo $m['a_nommer'] ? ' class="fiche-tete__attente"' : ''; ?>><?php the_title(); ?></h1>
			<?php if ( $m['fonction'] ) : ?><p class="fiche-tete__fonction"><?php echo esc_html( $m['fonction'] ); ?></p><?php endif; ?>
			<?php if ( $m['grade'] || $m['departement'] ) : ?>
				<p class="fiche-tete__reperes">
					<?php if ( $m['grade'] ) : ?><span><?php echo esc_html( $m['grade'] ); ?></span><?php endif; ?>
					<?php if ( $m['departement'] ) : ?><span><?php echo esc_html( $m['departement'] ); ?></span><?php endif; ?>
				</p>
			<?php endif; ?>
			<?php if ( $m['cv'] || ( $m['courriel'] && is_email( $m['courriel'] ) ) ) : ?>
				<div class="fiche-tete__actions">
					<?php if ( $m['cv'] ) : ?><a class="btn btn--or" href="<?php echo esc_url( $m['cv'] ); ?>" target="_blank" rel="noopener"><?php echo sueb_icone( 'document', 18 ); ?>Télécharger le CV</a><?php endif; ?>
					<?php if ( $m['courriel'] && is_email( $m['courriel'] ) ) : ?><a class="btn btn--verre" href="<?php echo esc_url( 'mailto:' . $m['courriel'] ); ?>"><?php echo sueb_icone( 'courriel', 18 ); ?>Écrire un courriel</a><?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		</div>
	</div>
</header>

<div class="conteneur page-corps fiche-corps">
	<div class="fiche-corps__grille">
		<dl class="fiche-infos" data-cv-infos>
			<div>
				<dt>Établissement</dt>
				<dd><?php if ( $etab_url ) : ?><a href="<?php echo esc_url( $etab_url ); ?>"><?php echo esc_html( $m['etab_nom'] ); ?></a><?php else : ?><?php echo esc_html( $m['etab_nom'] ); ?><?php endif; ?></dd>
			</div>
			<?php if ( $m['departement'] ) : ?>
				<div><dt>Département</dt><dd><a href="<?php echo esc_url( get_permalink( (int) sueb_meta( $m['id'], 'departement' ) ) ); ?>"><?php echo esc_html( $m['departement'] ); ?></a></dd></div>
			<?php endif; ?>
			<?php if ( $cours ) : ?>
				<div><dt>Enseigne à</dt><dd class="fiche-infos__etabs"><?php foreach ( $cours as $e ) : ?><a href="<?php echo esc_url( $e['url'] ); ?>"><img src="<?php echo esc_url( $e['logo'] ); ?>" alt="" width="20" height="20" loading="lazy"><?php echo esc_html( $e['sigle'] ?: $e['nom'] ); ?></a><?php endforeach; ?></dd></div>
			<?php endif; ?>
			<?php if ( $m['corps'] ) : ?>
				<div><dt>Catégorie</dt><dd><?php echo esc_html( $m['corps'] ); ?></dd></div>
			<?php endif; ?>
			<?php if ( $m['grade'] ) : ?>
				<div><dt>Grade</dt><dd><?php echo esc_html( $m['grade'] ); ?></dd></div>
			<?php endif; ?>
			<?php if ( $m['courriel'] && is_email( $m['courriel'] ) ) : ?>
				<div><dt>Courriel</dt><dd><a href="<?php echo esc_url( 'mailto:' . $m['courriel'] ); ?>"><?php echo esc_html( $m['courriel'] ); ?></a></dd></div>
			<?php endif; ?>
			<?php if ( $m['cv'] ) : ?>
				<div><dt>CV complet</dt><dd><a href="<?php echo esc_url( $m['cv'] ); ?>" target="_blank" rel="noopener">Télécharger le PDF</a></dd></div>
			<?php endif; ?>
		</dl>

		<?php echo sueb_cv_personnel( $m ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?>
	</div>

	<?php if ( $cours ) : ?>
		<section class="enseignements" id="enseignements" aria-labelledby="titre-enseignements">
			<header class="tete-section">
				<div>
					<p class="sur-titre">Enseignements</p>
					<h2 class="titre-section" id="titre-enseignements">Unités d’enseignement <span class="titre-section__compte"><?php echo (int) $nb_cours; ?></span></h2>
				</div>
				<p class="tete-section__chapo"><?php echo esc_html( sprintf( _n( 'Ses cours dans %d établissement de l’université.', 'Ses cours dans %d établissements de l’université.', count( $cours ) ), count( $cours ) ) ); ?> Choisissez une unité pour voir sa fiche, ou une filière pour son programme complet.</p>
			</header>
			<?php foreach ( $cours as $e ) : ?>
				<div class="enseignements__etab" style="--etab:<?php echo esc_attr( $e['couleur'] ); ?>">
					<h3 class="enseignements__titre"><a href="<?php echo esc_url( $e['url'] ); ?>"><img src="<?php echo esc_url( $e['logo'] ); ?>" alt="" width="32" height="32" loading="lazy"><span><strong><?php echo esc_html( $e['sigle'] ); ?></strong><?php echo esc_html( $e['nom'] ); ?></span></a></h3>
					<ul class="ues-liste ues-liste--prof">
						<li class="ue ue--tete" aria-hidden="true"><span>Code</span><span>Unité d’enseignement</span><span>Filière</span><span>Niveau</span><span>Crédits</span><span>Syllabus</span></li>
						<?php foreach ( $e['ues'] as $u ) : ?>
							<li class="ue">
								<span class="ue__code"><?php echo esc_html( $u['code'] ?: '—' ); ?></span>
								<a class="ue__titre" href="<?php echo esc_url( $u['url'] ); ?>"><?php echo esc_html( $u['titre'] ); ?></a>
								<a class="ue__filiere" href="<?php echo esc_url( $u['f_url'] ); ?>" data-libelle="Filière"><?php echo esc_html( $u['filiere'] ); ?></a>
								<span class="ue__niveau" data-libelle="Niveau"><?php echo esc_html( $u['niveau'] ); ?></span>
								<span class="ue__credits" data-libelle="Crédits"><?php if ( $u['credits'] ) : ?><strong><?php echo esc_html( $u['credits'] ); ?></strong> crédits<?php else : ?>—<?php endif; ?></span>
								<span class="ue__syllabus"><?php if ( $u['syllabus'] ) : ?><a href="<?php echo esc_url( $u['syllabus'] ); ?>" target="_blank" rel="noopener"><?php echo sueb_icone( 'document', 16 ); ?>Syllabus</a><?php else : ?><span class="attente">Syllabus à venir</span><?php endif; ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</section>
	<?php endif; ?>

	<?php if ( $voisins ) : ?>
		<section class="fiche-voisins" aria-labelledby="titre-voisins">
			<div class="fiche-voisins__tete">
				<h2 id="titre-voisins">Voir aussi</h2>
				<a class="lien-fleche" href="<?php echo esc_url( home_url( '/#personnel' ) ); ?>">Tout le personnel<?php echo sueb_icone( 'fleche', 18 ); ?></a>
			</div>
			<ul class="profils">
				<?php foreach ( $voisins as $v ) : ?>
					<?php echo sueb_carte_personnel( sueb_membre_personnel( $v ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
</div>
<?php
get_footer();
