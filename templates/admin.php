<?php
/**
 * Page de gestion du site (/gestion/), en plein écran, sans l'en-tête ni le pied du site
 * (sur le modèle des espaces de gestion des plateformes de préinscription et d'inscription) :
 * l'écran de connexion en deux volets, puis le tableau de bord monté par assets/js/admin.js.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$erreurs = array(
	'identifiants' => 'Identifiant ou mot de passe incorrect.',
	'droits'       => 'Ce compte n’a pas accès à la gestion du site.',
	'trop'         => 'Trop de tentatives. Réessayez dans quinze minutes.',
	'session'      => 'La page a expiré. Veuillez réessayer.',
);
$code   = isset( $_GET['erreur'] ) ? sanitize_key( wp_unslash( $_GET['erreur'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- simple affichage
$erreur = $erreurs[ $code ] ?? '';
$gerer  = sueb_peut_gerer();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'g-page' ); ?>>
<?php wp_body_open(); ?>
<a class="g-evitement" href="#g-contenu">Aller au contenu</a>

<?php if ( $gerer ) : ?>
	<div id="gestion-app" class="g-app">
		<p class="g-attente" role="status"><span class="g-roue" aria-hidden="true"></span>Chargement de l’espace de gestion…</p>
	</div>
	<noscript><p class="g-attente">JavaScript est nécessaire pour utiliser l’espace de gestion.</p></noscript>

<?php else : ?>
	<main class="g-connexion-page">
		<div class="g-connexion">
			<div class="g-connexion__volet">
				<img class="g-connexion__logo" src="<?php echo esc_url( sueb_logo() ); ?>" alt="" width="88" height="88">
				<p class="g-connexion__marque">Université d’Ebolowa</p>
				<h1>Gestion du site</h1>
				<p class="g-connexion__intro">L’espace où l’équipe de communication publie et tient à jour les contenus du site de l’université.</p>
				<ul class="g-connexion__points">
					<li><?php echo sueb_icone( 'fleche', 18 ); ?>Actualités, évènements et pages</li>
					<li><?php echo sueb_icone( 'fleche', 18 ); ?>Établissements, filières et personnel</li>
					<li><?php echo sueb_icone( 'fleche', 18 ); ?>Partenariats, page d’accueil et coordonnées</li>
				</ul>
			</div>

			<div class="g-connexion__formulaire">
				<?php if ( is_user_logged_in() ) : ?>
					<h2>Accès refusé</h2>
					<p class="g-alerte g-alerte--danger" role="alert">Votre compte n’a pas accès à la gestion du site.</p>
					<a class="g-btn g-btn--primaire g-btn--large" href="<?php echo esc_url( wp_logout_url( sueb_url_gestion() ) ); ?>">Changer de compte</a>
				<?php else : ?>
					<h2>Connexion</h2>
					<p class="g-connexion__aide">Réservé aux gestionnaires du site.</p>
					<?php if ( $erreur ) : ?>
						<p class="g-alerte g-alerte--danger" role="alert"><?php echo esc_html( $erreur ); ?></p>
					<?php endif; ?>
					<form class="g-connexion__champs" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sueb_connexion">
						<?php wp_nonce_field( 'sueb_connexion' ); ?>
						<div class="g-champ">
							<label for="c-id">Identifiant ou courriel</label>
							<input type="text" id="c-id" name="identifiant" autocomplete="username" required autofocus>
						</div>
						<div class="g-champ">
							<label for="c-mdp">Mot de passe</label>
							<div class="g-mdp">
								<input type="password" id="c-mdp" name="mdp" autocomplete="current-password" required>
								<button type="button" class="g-mdp__voir" aria-label="Afficher le mot de passe" aria-pressed="false" data-voir-mdp>Afficher</button>
							</div>
						</div>
						<label class="g-case"><input type="checkbox" name="memoriser" value="1"> Rester connecté</label>
						<button class="g-btn g-btn--primaire g-btn--large" type="submit">Se connecter</button>
					</form>
					<p class="g-connexion__oubli"><a href="<?php echo esc_url( wp_lostpassword_url( sueb_url_gestion() ) ); ?>">Mot de passe oublié ?</a></p>
				<?php endif; ?>
				<p class="g-connexion__retour"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">← Retour au site</a></p>
			</div>
		</div>
	</main>
	<script>
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-voir-mdp]');
		if (!b) return;
		var champ = document.getElementById('c-mdp');
		var voir = champ.type === 'password';
		champ.type = voir ? 'text' : 'password';
		b.textContent = voir ? 'Masquer' : 'Afficher';
		b.setAttribute('aria-pressed', voir ? 'true' : 'false');
		b.setAttribute('aria-label', voir ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
	});
	</script>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
