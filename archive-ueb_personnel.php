<?php
/**
 * La page du personnel (/personnel/) : l'annuaire complet, avec recherche et filtres.
 * L'accueil n'en montre que la direction (templates/accueil/personnel.php).
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
	</div>
</header>
<?php
get_template_part( 'templates/annuaire' );
get_footer();
