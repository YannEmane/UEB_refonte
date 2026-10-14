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
				<div><dt>Département</dt><dd><?php echo esc_html( $m['departement'] ); ?></dd></div>
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
