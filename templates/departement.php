<?php
/**
 * Page d'un département, troisième niveau du parcours (Académie > établissement >
 * département) : en-tête à la couleur de l'établissement, le chef de département
 * (portrait, nom, grade, présentation, lien vers sa fiche du Personnel), la
 * présentation du département, puis ses filières en cartes (diplôme, résumé,
 * niveaux, nombre d'UE) et les autres départements de l'établissement.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$id      = (int) $args['id'];
$racine  = sueb_racine( $id );
$sigle   = strtoupper( (string) sueb_meta( $racine, 'sigle' ) );
$fixe    = sueb_etablissements()[ $sigle ] ?? array();
$couleur = (string) sueb_meta( $racine, 'couleur' ) ?: ( $fixe['couleur'] ?? '#163d2b' );
$groupe  = (bool) sueb_meta( $id, 'groupe' );
$code    = (string) sueb_meta( $id, 'sigle' );
$titre   = get_the_title( $id );
$etab    = get_the_title( $racine );

$filieres = array_map( 'sueb_filiere', array_values( get_children( array( 'post_parent' => $id, 'post_type' => 'ueb_structure', 'post_status' => 'publish', 'orderby' => 'menu_order title', 'order' => 'ASC' ) ) ) );
$nb_ue    = array_sum( array_column( $filieres, 'nb_ue' ) );
$chef     = $groupe ? null : sueb_chef_departement( $id );
$resp     = (string) sueb_meta( $id, 'responsable' );
$texte    = trim( (string) get_post_field( 'post_content', $id ) );
$nf       = count( $filieres );
/* « de la FS », « de l’ENSET », « de l’ISABEE » : élision devant une voyelle. */
$de_sigle = ( preg_match( '/^[AEIOUY]/i', $sigle ) ? 'de l’' : ( preg_match( '/^facult/i', $etab ) ? 'de la ' : 'du ' ) ) . ( $sigle ?: $etab );

/* Les enseignants du département : ceux qui y sont rattachés dans le Personnel. */
$equipe = array();
foreach ( get_posts( array( 'post_type' => 'ueb_personnel', 'numberposts' => 12, 'meta_key' => '_sueb_departement', 'meta_value' => $id, 'orderby' => 'menu_order title', 'order' => 'ASC' ) ) as $pers ) {
	if ( ! $chef || $pers->ID !== $chef['id'] ) {
		$equipe[] = sueb_membre_personnel( $pers );
	}
}

$autres = array_filter( get_children( array( 'post_parent' => $racine, 'post_type' => 'ueb_structure', 'post_status' => 'publish', 'orderby' => 'menu_order title', 'order' => 'ASC' ) ), function ( $d ) use ( $id ) {
	return $d->ID !== $id && ! sueb_meta( $d->ID, 'groupe' );
} );
?>
<article class="dep" style="--etab:<?php echo esc_attr( $couleur ); ?>;--sur-etab:<?php echo esc_attr( sueb_texte_sur( $couleur ) ); ?>">
	<header class="dep-tete">
		<div class="conteneur">
			<ol class="fil-ariane">
				<li><a href="<?php echo esc_url( sueb_url_academie() ); ?>">Académie</a></li>
				<li><a href="<?php echo esc_url( get_permalink( $racine ) ); ?>"><?php echo esc_html( $sigle ?: $etab ); ?></a></li>
				<li aria-current="page"><?php echo esc_html( $titre ); ?></li>
			</ol>
			<p class="dep-tete__sur"><img src="<?php echo esc_url( sueb_image_url( $racine, 'medium', 'logo' ) ?: sueb_logo( $sigle ) ); ?>" alt="" width="28" height="28"><?php echo esc_html( $groupe ? $etab : 'Département' . ( $code ? ' · ' . $code : '' ) ); ?></p>
			<h1 class="dep-tete__titre"><?php echo esc_html( $titre ); ?></h1>
			<dl class="dep-tete__chiffres">
				<div><dd><?php echo (int) $nf; ?></dd><dt><?php echo 1 === $nf ? 'filière' : 'filières'; ?></dt></div>
				<?php if ( $nb_ue ) : ?><div><dd><?php echo (int) $nb_ue; ?></dd><dt>unités d’enseignement</dt></div><?php endif; ?>
				<div><dd class="dep-tete__etab"><a href="<?php echo esc_url( get_permalink( $racine ) ); ?>"><?php echo esc_html( $sigle ?: $etab ); ?></a></dd><dt><?php echo esc_html( $fixe['ville'] ?? 'Établissement' ); ?></dt></div>
			</dl>
		</div>
	</header>

	<?php if ( ! $groupe ) : ?>
		<section class="dep-intro" aria-label="Le département">
			<div class="conteneur dep-intro__grille">
				<div class="dep-chef revele" aria-labelledby="titre-chef">
					<p class="sur-titre">Chef de département</p>
					<?php if ( $chef ) : ?>
						<a class="dep-chef__portrait" href="<?php echo esc_url( $chef['url'] ); ?>" tabindex="-1" aria-hidden="true"><span class="profil__portrait"><?php echo sueb_portrait_personnel( $chef, 'eager' ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?></span></a>
						<h2 class="dep-chef__nom" id="titre-chef"><a href="<?php echo esc_url( $chef['url'] ); ?>"><?php echo esc_html( $chef['nom'] ); ?></a></h2>
						<?php if ( $chef['grade'] || $chef['specialite'] ) : ?>
							<ul class="puces">
								<?php if ( $chef['grade'] ) : ?><li><?php echo esc_html( $chef['grade'] ); ?></li><?php endif; ?>
								<?php if ( $chef['specialite'] ) : ?><li><?php echo esc_html( $chef['specialite'] ); ?></li><?php endif; ?>
							</ul>
						<?php endif; ?>
						<p class="dep-chef__bio"><?php echo esc_html( sueb_bio_courte( $chef, 45 ) ); ?></p>
						<a class="lien-texte" href="<?php echo esc_url( $chef['url'] ); ?>">Voir sa fiche et son CV</a>
					<?php else : ?>
						<span class="dep-chef__portrait dep-chef__portrait--vide" aria-hidden="true"><?php echo esc_html( $resp ? sueb_initiales_personne( $resp ) : '?' ); ?></span>
						<h2 class="dep-chef__nom<?php echo $resp ? '' : ' attente'; ?>" id="titre-chef"><?php echo esc_html( $resp ?: 'À nommer' ); ?></h2>
						<p class="dep-chef__bio">Sa fiche et son parcours seront bientôt publiés.</p>
					<?php endif; ?>
				</div>
				<div class="dep-apropos revele">
					<p class="sur-titre">Le département</p>
					<h2 class="titre-section">À propos</h2>
					<div class="apropos__prose">
						<?php if ( $texte ) : ?>
							<?php echo wp_kses_post( apply_filters( 'the_content', $texte ) ); ?>
						<?php else : ?>
							<p><?php echo esc_html( sprintf( 'Le %s %s rassemble %s%s. Chaque filière y est présentée avec ses niveaux et ses unités d’enseignement : crédits, volume horaire, syllabus et enseignants.', preg_match( '/^département/iu', $titre ) ? mb_strtolower( mb_substr( $titre, 0, 1 ) ) . mb_substr( $titre, 1 ) : 'département ' . $titre, $de_sigle, $nf ? sprintf( _n( '%d filière', '%d filières', $nf ), $nf ) : 'ses filières', $nb_ue ? sprintf( ' et %d unités d’enseignement', $nb_ue ) : '' ) ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="dep-filieres" id="filieres" aria-labelledby="titre-filieres">
		<div class="conteneur">
			<header class="tete-section revele">
				<div>
					<p class="sur-titre">Formations</p>
					<h2 class="titre-section" id="titre-filieres">Filières <span class="titre-section__compte"><?php echo (int) $nf; ?></span></h2>
				</div>
				<p class="tete-section__chapo">Choisissez une filière pour découvrir ses niveaux et, pour chacun, ses unités d’enseignement : code, crédits, volume horaire, syllabus et enseignants.</p>
			</header>
			<?php if ( ! $filieres ) : ?>
				<p class="vide">Les filières de ce département seront bientôt publiées.</p>
			<?php else : ?>
				<ul class="cartes-filieres">
					<?php foreach ( $filieres as $k => $f ) : ?>
						<li class="carte-filiere revele" style="--d:<?php echo esc_attr( round( ( $k % 3 ) * .06, 2 ) ); ?>s">
							<?php if ( $f['photo'] ) : ?><img class="carte-filiere__photo" src="<?php echo esc_url( $f['photo'] ); ?>" alt="" loading="lazy" width="960" height="640"><?php endif; ?>
							<div class="carte-filiere__haut">
								<?php if ( $f['diplome'] ) : ?><span class="pastille"><?php echo esc_html( $f['diplome'] ); ?></span><?php endif; ?>
								<?php if ( $f['code'] ) : ?><span class="carte-filiere__code"><?php echo esc_html( $f['code'] ); ?></span><?php endif; ?>
							</div>
							<h3 class="carte-filiere__titre"><a href="<?php echo esc_url( $f['url'] ); ?>"><?php echo esc_html( $f['titre'] ); ?></a></h3>
							<p class="carte-filiere__resume"><?php echo esc_html( $f['resume'] ?: 'La présentation de cette filière sera bientôt publiée.' ); ?></p>
							<div class="carte-filiere__pied">
								<?php if ( $f['niveaux'] ) : ?>
									<ul class="niveaux-puces" aria-label="Niveaux">
										<?php foreach ( $f['niveaux'] as $niveau => $ues ) : ?><li class="<?php echo $ues ? 'est-plein' : ''; ?>" title="<?php echo esc_attr( sueb_nom_niveau( $niveau ) . ( $ues ? ' : ' . count( $ues ) . ' UE' : '' ) ); ?>"><?php echo esc_html( preg_replace( '/^Niveau (\d+)$/', 'N$1', $niveau ) ); ?></li><?php endforeach; ?>
									</ul>
								<?php endif; ?>
								<span class="carte-filiere__compte"><?php echo $f['nb_ue'] ? esc_html( $f['nb_ue'] . ' UE · ' . $f['credits'] . ' crédits' ) : 'Programme à venir'; ?></span>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( $equipe ) : ?>
		<section class="dep-equipe" aria-labelledby="titre-dep-equipe">
			<div class="conteneur">
				<header class="tete-section revele">
					<div>
						<p class="sur-titre">Enseignants</p>
						<h2 class="titre-section" id="titre-dep-equipe">L’équipe du département</h2>
					</div>
				</header>
				<ul class="profils"><?php foreach ( $equipe as $m ) { echo sueb_carte_personnel( $m ); } // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?></ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $autres ) : ?>
		<nav class="dep-autres" aria-labelledby="titre-dep-autres">
			<div class="conteneur">
				<h2 class="dep-autres__titre" id="titre-dep-autres">Les autres départements <?php echo esc_html( $de_sigle ); ?></h2>
				<ul>
					<?php foreach ( $autres as $d ) : ?><li><a href="<?php echo esc_url( get_permalink( $d ) ); ?>"><?php echo esc_html( $d->post_title ); ?></a></li><?php endforeach; ?>
				</ul>
			</div>
		</nav>
	<?php endif; ?>
</article>
