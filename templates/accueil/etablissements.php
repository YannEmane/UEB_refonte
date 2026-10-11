<?php
/**
 * Nos établissements et écoles : une grille de cartes de couleur unie (couleur
 * identitaire de chaque établissement) avec son logo, son nom et une photo
 * d'étudiant(e). Un clic ouvre la page de l'établissement (départements,
 * filières, niveaux, UE, équipe, campus) ; la carte s'y transforme en bandeau.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$arbre = $args['arbre'];

/* Ordre d'affichage, en grille de 3 × 3 : les facultés, puis les écoles, puis l'institut. */
$ordre  = array( 'FS', 'FSJP', 'FALSH', 'FSEG', 'FMSP', 'ENSET', 'ESTLC', 'ENSTMO', 'ISABEE' );
$cartes = array();
$autres = array();
foreach ( $arbre as $e ) {
	$rang = array_search( strtoupper( $e['sigle'] ), $ordre, true );
	if ( false === $rang ) {
		$autres[] = $e;
	} else {
		$cartes[ $rang ] = $e;
	}
}
ksort( $cartes );
$cartes = array_merge( array_values( $cartes ), $autres );
?>
<section class="section etablissements" id="etablissements" aria-labelledby="titre-etab">
	<div class="conteneur">
		<header class="section__tete revele">
			<p class="surtitre">Nos établissements et écoles</p>
			<h2 id="titre-etab">Facultés, écoles et institut</h2>
			<p class="section__chapo">Découvrez les établissements de l’UEb et parcourez leurs départements, leurs filières et leurs unités d’enseignement, jusqu’au syllabus et aux crédits de chaque cours.</p>
		</header>

		<?php if ( ! $cartes ) : ?>
			<p class="vide">Les établissements seront bientôt présentés ici.</p>
		<?php endif; ?>

		<ol class="mosaique">
			<?php foreach ( $cartes as $i => $e ) :
				$etudiant = sueb_photo_etudiant( $e['sigle'] );
				$etiquette = $e['titre'];
				?>
				<li class="mosaique__carte revele" id="etab-<?php echo esc_attr( strtolower( $e['sigle'] ) ); ?>" style="view-transition-name:etab-<?php echo esc_attr( strtolower( $e['sigle'] ) ); ?>;--etab:<?php echo esc_attr( $e['couleur'] ); ?>;--texte:<?php echo esc_attr( sueb_texte_sur( $e['couleur'] ) ); ?>;--d:<?php echo esc_attr( round( $i * .07, 2 ) ); ?>s">
					<?php if ( ! empty( $e['provisoire'] ) ) : ?>
						<a class="mosaique__lien" href="<?php echo esc_url( SUEB_LIENS['preinscription'] ); ?>" aria-label="<?php echo esc_attr( $etiquette . ' : préinscription en ligne' ); ?>">
					<?php else : ?>
						<a class="mosaique__lien" href="<?php echo esc_url( get_permalink( $e['id'] ) ); ?>" aria-label="<?php echo esc_attr( $etiquette . ' : départements, filières et équipe' ); ?>">
					<?php endif; ?>
						<span class="mosaique__logo"><img src="<?php echo esc_url( $e['logo'] ); ?>" alt="" width="64" height="64" loading="lazy"></span>
						<span class="mosaique__texte">
							<span class="mosaique__sigle"><?php echo esc_html( $e['sigle'] ); ?></span>
							<span class="mosaique__nom"><?php echo esc_html( $e['titre'] ); ?></span>
						</span>
						<?php if ( $etudiant ) : ?>
							<img class="mosaique__etudiant" src="<?php echo esc_url( $etudiant ); ?>" alt="" loading="lazy" style="view-transition-name:etudiant-<?php echo esc_attr( strtolower( $e['sigle'] ) ); ?>">
						<?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
