<?php
/**
 * La page Académie (/academie/, archive des structures) : le premier niveau du
 * parcours académique. Les établissements de l'UEb y sont présentés avec les
 * mêmes cartes que sur l'accueil (templates/accueil/etablissements.php) ; une
 * carte ouvre la page de l'établissement (direction, missions, départements),
 * puis le département (chef, filières), la filière (niveaux, UE, enseignants).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$arbre = sueb_arbre_formation();

/* Les chiffres de l'en-tête : établissements, départements, filières, villes. */
$nb = array( 'etab' => count( $arbre ), 'dep' => 0, 'fil' => 0 );
$villes = array();
foreach ( $arbre as $e ) {
	foreach ( $e['enfants'] as $d ) {
		$nb['dep'] += sueb_meta( $d['id'], 'groupe' ) ? 0 : 1;
	}
	$nb['fil'] += sueb_compter( $e, 2 );
	if ( $e['ville'] ) {
		$villes[ $e['ville'] ] = true;
	}
}
$chiffres = array_filter( array(
	'établissements' => $nb['etab'],
	'départements'   => $nb['dep'],
	'filières'       => $nb['fil'],
	'villes'         => count( $villes ),
) );

get_header();
?>
<header class="acad-tete">
	<div class="conteneur">
		<ol class="fil-ariane"><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li><li aria-current="page">Académie</li></ol>
		<div class="acad-tete__ligne">
			<div>
				<h1 class="acad-tete__titre">Académie</h1>
				<p class="acad-tete__chapo">Facultés, grandes écoles et institut : choisissez un établissement pour rencontrer sa direction, découvrir ses missions et ses départements, puis parcourir ses filières jusqu’aux unités d’enseignement et à leurs enseignants.</p>
			</div>
			<?php if ( $chiffres ) : ?>
				<dl class="acad-chiffres">
					<?php foreach ( $chiffres as $nom => $n ) : ?>
						<div><dt><?php echo esc_html( $nom ); ?></dt><dd><?php echo (int) $n; ?></dd></div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
		</div>
	</div>
</header>

<?php get_template_part( 'templates/accueil/etablissements', null, array( 'arbre' => $arbre, 'sans_tete' => true ) ); ?>

<div class="conteneur acad-pied">
	<aside class="acad-appel revele">
		<div>
			<h2>Rejoindre l’Université d’Ebolowa</h2>
			<p>La préinscription se fait en ligne, pour tous les établissements.</p>
		</div>
		<a class="btn btn--or" href="<?php echo esc_url( sueb_lien( 'preinscription' ) ); ?>">Préinscription en ligne</a>
	</aside>
</div>
<?php
get_footer();
