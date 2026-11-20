<?php
/**
 * Page introuvable. Sert aussi de réponse aux adresses de WordPress fermées
 * aux visiteurs (wp-login.php, wp-admin : voir inc/protection.php).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="page-tete">
	<div class="conteneur">
		<ol class="fil-ariane"><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li></ol>
		<h1>Page introuvable</h1>
		<p class="page-tete__chapo">L’adresse demandée n’existe pas ou n’est plus disponible.</p>
	</div>
</header>
<div class="conteneur page-corps">
	<p><a class="btn btn--plein" href="<?php echo esc_url( home_url( '/' ) ); ?>">Retour à l’accueil</a></p>
</div>
<?php
get_footer();
