<?php
/**
 * La page du personnel (/personnel/) : l'annuaire complet, avec recherche et filtres.
 * L'accueil n'en montre que le Recteur, le PCA et les vice-recteurs (templates/accueil/personnel.php).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="page-tete">
	<div class="conteneur">
		<ol class="fil-ariane"><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li></ol>
		<p class="surtitre surtitre--clair">Personnels de l’UEb</p>
		<h1>Celles et ceux qui font l’université</h1>
		<p class="page-tete__chapo">La direction de l’université, pôle par pôle, puis les doyens et directeurs des neuf établissements. Choisissez une personne pour lire son parcours. Les chefs de département et les enseignants sont présentés sur la page de leur établissement.</p>
	</div>
</header>
<?php
get_template_part( 'templates/annuaire' );
get_footer();
