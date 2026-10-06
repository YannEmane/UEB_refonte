<?php
/**
 * Nos établissements et écoles : une mosaïque de cartes colorées (couleur
 * identitaire de chaque établissement) avec son logo, son nom et une photo
 * d'étudiant(e). Un clic ouvre l'explorateur « poupée russe » : départements >
 * filières > unités d'enseignement > syllabus et crédits.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$arbre = $args['arbre'];

/* Ordre d'affichage : la première carte est la grande ; les couleurs voisines sont écartées. */
$ordre  = array( 'FSEG', 'FSJP', 'ISABEE', 'FMSP', 'FALSH', 'FS', 'ENSET', 'ESTLC', 'ENSTMO' );
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
$total  = count( $cartes );
/* Après la grande carte et 4 petites, les cartes se rangent par 3 : le reste s'étire sur la dernière ligne. */
$reste = $total > 5 ? ( $total - 5 ) % 3 : 0;
?>
<section class="section etablissements" id="etablissements" aria-labelledby="titre-etab">
	<div class="conteneur">
		<header class="section__tete revele">
			<p class="surtitre">Nos établissements et écoles</p>
			<h2 id="titre-etab">Facultés, écoles et institut</h2>
			<p class="section__chapo">Chaque établissement s’ouvre sur ses départements, puis ses filières, ses unités d’enseignement et enfin le syllabus de chaque cours, avec ses crédits.</p>
		</header>

		<?php if ( ! $cartes ) : ?>
			<p class="vide">Les établissements seront bientôt présentés ici.</p>
		<?php endif; ?>

		<ol class="mosaique">
			<?php foreach ( $cartes as $i => $e ) :
				$classes = 'mosaique__carte revele';
				if ( 0 === $i && $total > 4 ) {
					$classes .= ' mosaique__carte--grande';
				}
				if ( $reste && $i >= $total - $reste ) {
					$classes .= 1 === $reste ? ' mosaique__carte--large' : ' mosaique__carte--demi';
				}
				if ( strlen( $e['titre'] ) > 46 ) {
					$classes .= ' mosaique__carte--long';
				}
				$etudiant = sueb_photo_etudiant( $e['sigle'] );
				$etiquette = $e['titre'];
				?>
				<li class="<?php echo esc_attr( $classes ); ?>" id="etab-<?php echo esc_attr( strtolower( $e['sigle'] ) ); ?>" style="--etab:<?php echo esc_attr( $e['couleur'] ); ?>;--texte:<?php echo esc_attr( sueb_texte_sur( $e['couleur'] ) ); ?>;--d:<?php echo esc_attr( round( $i * .07, 2 ) ); ?>s">
					<?php if ( ! empty( $e['provisoire'] ) ) : ?>
						<a class="mosaique__lien" href="<?php echo esc_url( SUEB_LIENS['preinscription'] ); ?>" aria-label="<?php echo esc_attr( $etiquette . ' : préinscription en ligne' ); ?>">
					<?php else : ?>
						<button class="mosaique__lien" type="button" data-explorer="<?php echo (int) $e['id']; ?>" aria-label="<?php echo esc_attr( $etiquette . ' : explorer la formation' ); ?>">
					<?php endif; ?>
						<span class="mosaique__logo"><img src="<?php echo esc_url( $e['logo'] ); ?>" alt="" width="64" height="64" loading="lazy"></span>
						<span class="mosaique__nom"><?php echo esc_html( $e['titre'] ); ?></span>
						<?php if ( $etudiant ) : ?>
							<img class="mosaique__etudiant" src="<?php echo esc_url( $etudiant ); ?>" alt="" loading="lazy">
						<?php endif; ?>
					<?php echo ! empty( $e['provisoire'] ) ? '</a>' : '</button>'; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<dialog class="explorateur" id="explorateur" aria-labelledby="explorateur-titre">
	<div class="explorateur__tete">
		<img class="explorateur__logo" src="" alt="" width="48" height="48" data-x-logo>
		<div>
			<p class="explorateur__fil" data-x-fil></p>
			<h2 class="explorateur__titre" id="explorateur-titre" data-x-titre></h2>
		</div>
		<button class="explorateur__fermer" type="button" data-fermer aria-label="Fermer l’explorateur"><?php echo sueb_icone( 'fermer', 24 ); ?></button>
	</div>
	<div class="explorateur__colonnes" data-x-colonnes></div>
</dialog>

<script type="application/json" id="donnees-formation"><?php echo wp_json_encode( $arbre, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ); ?></script>