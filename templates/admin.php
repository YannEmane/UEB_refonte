<?php
/**
 * Page de gestion du site (/gestion/) : connexion, puis l'interface de
 * gestion des contenus montée par assets/js/admin.js.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

get_header();

$erreurs = array(
	'identifiants' => 'Identifiant ou mot de passe incorrect.',
	'droits'       => 'Ce compte n’a pas accès à la gestion du site.',
	'trop'         => 'Trop de tentatives. Réessayez dans quinze minutes.',
	'session'      => 'La page a expiré. Veuillez réessayer.',
);
$erreur = isset( $_GET['erreur'], $erreurs[ $_GET['erreur'] ] ) ? $erreurs[ $_GET['erreur'] ] : '';
?>
<header class="page-tete">
	<div class="conteneur">
		<ol class="fil-ariane"><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li></ol>
		<h1>Gestion du site</h1>
	</div>
</header>
<div class="conteneur page-corps">
	<?php if ( sueb_peut_gerer() ) : ?>
		<div id="gestion-app" class="gestion">
			<p class="gestion__attente">Chargement de l’interface…</p>
		</div>
		<noscript><p>JavaScript est nécessaire pour utiliser la page de gestion.</p></noscript>
	<?php elseif ( is_user_logged_in() ) : ?>
		<div class="connexion">
			<p class="connexion__erreur" role="alert">Votre compte n’a pas accès à la gestion du site.</p>
			<p><a class="btn btn--plein" href="<?php echo esc_url( wp_logout_url( sueb_url_gestion() ) ); ?>">Changer de compte</a></p>
		</div>
	<?php else : ?>
		<form class="connexion" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<h2>Connexion</h2>
			<p>Réservé aux gestionnaires du site de l’Université d’Ebolowa.</p>
			<?php if ( $erreur ) : ?><p class="connexion__erreur" role="alert"><?php echo esc_html( $erreur ); ?></p><?php endif; ?>
			<input type="hidden" name="action" value="sueb_connexion">
			<?php wp_nonce_field( 'sueb_connexion' ); ?>
			<label for="c-id">Identifiant ou courriel</label>
			<input type="text" id="c-id" name="identifiant" autocomplete="username" required>
			<label for="c-mdp">Mot de passe</label>
			<input type="password" id="c-mdp" name="mdp" autocomplete="current-password" required>
			<label class="connexion__memo"><input type="checkbox" name="memoriser" value="1"> Rester connecté</label>
			<button class="btn btn--plein" type="submit">Se connecter</button>
			<p class="connexion__oubli"><a href="<?php echo esc_url( wp_lostpassword_url( sueb_url_gestion() ) ); ?>">Mot de passe oublié ?</a></p>
		</form>
	<?php endif; ?>
</div>
<?php
get_footer();