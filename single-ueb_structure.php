<?php
/**
 * Page d'un établissement, d'un département, d'une filière ou d'une UE.
 * Un établissement a sa propre page complète (templates/etablissement.php).
 * Les autres niveaux : fil d'Ariane jusqu'à l'établissement et repères chiffrés,
 * puis l'index des filières (département) ou la fiche de l'UE et ses éléments
 * constitutifs. Une filière a sa propre page (templates/filiere.php).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();

if ( 0 === sueb_profondeur( get_the_ID() ) ) {
	get_template_part( 'templates/etablissement', null, array( 'page' => sueb_page_etablissement( get_the_ID() ) ) );
	get_footer();
	return;
}

/* Une filière a sa propre page : présentation et programme année par année (templates/filiere.php). */
if ( 2 === sueb_profondeur( get_the_ID() ) ) {
	get_template_part( 'templates/filiere', null, array( 'id' => get_the_ID() ) );
	get_footer();
	return;
}

$id       = get_the_ID();
$niveaux  = sueb_niveaux();
$prof     = min( 3, sueb_profondeur( $id ) );
$racine   = sueb_racine( $id );
$sigle    = (string) sueb_meta( $racine, 'sigle' );
$fixe     = sueb_etablissements()[ strtoupper( $sigle ) ] ?? array( 'couleur' => '' );
$couleur  = sueb_meta( $racine, 'couleur' ) ?: $fixe['couleur'];
$syllabus = (int) sueb_meta( $id, 'syllabus' );
$parent   = wp_get_post_parent_id( $id );
$titre    = get_the_title();
$surtitre = $niveaux[ $prof ]['un'];
$reperes  = array(); /* Les chiffres et noms clés, sous le titre : libellé => valeur (ou array( valeur, lien )). */

if ( 1 === $prof ) {
	/* Un département (ou un simple groupe de filières) : ses filières, résumées. */
	$filieres = array_map( 'sueb_filiere', array_values( get_children( array( 'post_parent' => $id, 'post_type' => 'ueb_structure', 'post_status' => 'publish', 'orderby' => 'menu_order title', 'order' => 'ASC' ) ) ) );
	$nb_ue    = array_sum( array_column( $filieres, 'nb_ue' ) );
	$code_dep = (string) sueb_meta( $id, 'sigle' );
	$surtitre = sueb_meta( $id, 'groupe' ) ? 'Filières' : 'Département' . ( $code_dep ? ' · ' . $code_dep : '' );
	$reperes  = array(
		'Filières'                 => (string) count( $filieres ),
		'Unités d’enseignement'    => $nb_ue ? (string) $nb_ue : '',
		'Chef de département'      => (string) sueb_meta( $id, 'responsable' ),
		'Établissement'            => array( $sigle ?: get_the_title( $racine ), get_permalink( $racine ) ),
	);
} else {
	$surtitre .= sueb_meta( $id, 'code' ) ? ' · ' . sueb_meta( $id, 'code' ) : '';
}
$reperes = array_filter( $reperes );
?>
<header class="page-tete page-tete--structure" style="--etab:<?php echo esc_attr( $couleur ); ?>">
	<div class="conteneur">
		<ol class="fil-ariane">
			<li><a href="<?php echo esc_url( home_url( '/#etablissements' ) ); ?>">Établissements</a></li>
			<?php foreach ( array_reverse( get_post_ancestors( $id ) ) as $a ) : ?>
				<li><a href="<?php echo esc_url( get_permalink( $a ) ); ?>"><?php echo esc_html( sueb_meta( $a, 'sigle' ) ?: sueb_titre_et_code( get_the_title( $a ) )[0] ); ?></a></li>
			<?php endforeach; ?>
		</ol>
		<p class="surtitre surtitre--clair"><?php echo esc_html( $surtitre ); ?></p>
		<h1><?php echo esc_html( $titre ); ?></h1>
		<?php if ( $reperes ) : ?>
			<dl class="reperes">
				<?php foreach ( $reperes as $libelle => $valeur ) : ?>
					<div>
						<dt><?php echo esc_html( $libelle ); ?></dt>
						<dd<?php echo ctype_digit( is_array( $valeur ) ? '' : $valeur ) ? ' class="reperes__nombre"' : ''; ?>>
							<?php if ( is_array( $valeur ) ) : ?>
								<a href="<?php echo esc_url( $valeur[1] ); ?>"><?php echo esc_html( $valeur[0] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $valeur ); ?>
							<?php endif; ?>
						</dd>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
	</div>
</header>
<div class="conteneur page-corps" style="--etab:<?php echo esc_attr( $couleur ); ?>">
	<?php if ( trim( get_the_content() ) ) : ?>
		<div class="prose"><?php the_content(); ?></div>
	<?php endif; ?>

	<?php if ( 1 === $prof ) : ?>
		<section class="cursus" aria-labelledby="titre-cursus">
			<header class="cursus__tete">
				<h2 class="section__titre-2" id="titre-cursus">Filières</h2>
				<p>Choisissez une filière pour parcourir ses unités d’enseignement, niveau par niveau, avec leurs crédits, leur volume horaire et leur syllabus.</p>
			</header>
			<?php if ( ! $filieres ) : ?>
				<p class="vide">Filières en cours de publication.</p>
			<?php else : ?>
				<ol class="cursus__liste">
					<?php foreach ( $filieres as $i => $f ) : ?>
						<li class="cursus__item revele" style="--d:<?php echo esc_attr( min( $i, 8 ) * .05 ); ?>s">
							<a class="cursus__lien" href="<?php echo esc_url( $f['url'] ); ?>">
								<span class="cursus__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
								<span class="cursus__corps">
									<span class="cursus__titre"><?php echo esc_html( $f['titre'] ); ?></span>
									<span class="cursus__meta">
										<?php if ( $f['code'] ) : ?><span class="cursus__code"><?php echo esc_html( $f['code'] ); ?></span><?php endif; ?>
										<?php if ( $f['diplome'] ) : ?><span><?php echo esc_html( $f['diplome'] ); ?></span><?php endif; ?>
										<span><?php echo $f['nb_ue'] ? esc_html( $f['nb_ue'] . ' UE · ' . $f['credits'] . ' crédits' ) : 'Programme en cours de publication'; ?></span>
									</span>
								</span>
								<?php if ( $f['niveaux'] ) : ?>
									<span class="cursus__niveaux" aria-label="<?php echo esc_attr( sueb_niveaux_courts( $f['niveaux'] ) ); ?>">
										<?php foreach ( $f['niveaux'] as $niveau => $ues ) : ?>
											<span class="<?php echo $ues ? 'est-plein' : ''; ?>" title="<?php echo esc_attr( sueb_nom_niveau( $niveau ) . ( $ues ? ' : ' . count( $ues ) . ' UE' : '' ) ); ?>"><?php echo esc_html( preg_replace( '/^Niveau (\d+)$/', 'N$1', $niveau ) ); ?></span>
										<?php endforeach; ?>
									</span>
								<?php endif; ?>
								<span class="cursus__fleche" aria-hidden="true"><?php echo sueb_icone( 'fleche', 18 ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</section>

	<?php else : ?>
		<div class="fiche-ue" style="border-radius:var(--r-carte);max-width:720px">
			<dl>
				<div><dt>Crédits</dt><dd><?php echo esc_html( sueb_meta( $id, 'credits' ) ?: '—' ); ?></dd></div>
				<div><dt>Semestre</dt><dd><?php echo esc_html( sueb_meta( $id, 'semestre' ) ?: '—' ); ?></dd></div>
				<div><dt>Volume</dt><dd style="font-size:1rem"><?php echo esc_html( sueb_meta( $id, 'volume' ) ?: '—' ); ?></dd></div>
			</dl>
			<?php if ( $syllabus ) : ?>
				<a class="btn btn--plein" href="<?php echo esc_url( wp_get_attachment_url( $syllabus ) ); ?>" target="_blank" rel="noopener"><?php echo sueb_icone( 'document', 18 ); ?>Télécharger le syllabus</a>
			<?php else : ?>
				<p class="vide">Syllabus bientôt disponible.</p>
			<?php endif; ?>
		</div>
		<?php
		/* Les éléments constitutifs, un par ligne : « code | intitulé | crédits | volume ». */
		$elements = array_filter( array_map( 'trim', explode( "\n", (string) sueb_meta( $id, 'elements' ) ) ) );
		if ( $elements ) :
			?>
			<h2 class="section__titre-2" style="margin-top:2rem">Éléments constitutifs</h2>
			<table class="ues ues--cadre" style="max-width:960px">
				<thead><tr><th scope="col">Code</th><th scope="col">Élément constitutif</th><th scope="col">Crédits</th><th scope="col">Volume horaire</th></tr></thead>
				<tbody>
					<?php foreach ( $elements as $ligne ) : list( $ec_code, $ec_titre, $ec_credits, $ec_volume ) = array_pad( array_map( 'trim', explode( '|', $ligne ) ), 4, '' ); ?>
						<tr>
							<td class="ues__code"><?php echo esc_html( $ec_code ); ?></td>
							<th scope="row"><?php echo esc_html( $ec_titre ); ?></th>
							<td><?php if ( '' !== $ec_credits ) : ?><span class="ues__credits"><?php echo esc_html( $ec_credits ); ?></span><?php endif; ?></td>
							<td class="ues__volume"><?php echo esc_html( $ec_volume ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	<?php endif; ?>
</div>
<?php
get_footer();
