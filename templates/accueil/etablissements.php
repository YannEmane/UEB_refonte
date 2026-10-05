<?php
/**
 * Nos établissements et écoles : une ligne par établissement, photos aux
 * extrémités, puis l'explorateur « poupée russe » : départements >
 * filières > unités d'enseignement > syllabus et crédits.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$arbre  = $args['arbre'];
$villes = array_values( array_unique( array_filter( array_column( $arbre, 'ville' ) ) ) );
?>
<section class="section etablissements" id="etablissements" aria-labelledby="titre-etab">
	<div class="conteneur">
		<header class="section__tete section__tete--ligne revele">
			<div>
				<p class="surtitre">Nos établissements et écoles</p>
				<h2 id="titre-etab">Facultés, écoles et institut</h2>
				<p class="section__chapo">Chaque établissement s’ouvre sur ses départements, puis ses filières, ses unités d’enseignement et enfin le syllabus de chaque cours, avec ses crédits.</p>
			</div>
			<?php if ( count( $villes ) > 1 ) : ?>
				<div class="filtres" role="group" aria-label="Filtrer par ville" data-filtres>
					<button type="button" aria-pressed="true" data-ville="">Toutes les villes</button>
					<?php foreach ( $villes as $v ) : ?>
						<button type="button" aria-pressed="false" data-ville="<?php echo esc_attr( $v ); ?>"><?php echo esc_html( $v ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</header>

		<?php if ( ! $arbre ) : ?>
			<p class="vide">Les établissements seront bientôt présentés ici.</p>
		<?php endif; ?>

		<ol class="etabs" data-etabs>
			<?php foreach ( $arbre as $i => $e ) :
				$nb_dep = sueb_compter( $e, 1 );
				$nb_fil = sueb_compter( $e, 2 );
				$nb_ue  = sueb_compter( $e, 3 );
				?>
				<li class="etab revele" id="etab-<?php echo esc_attr( strtolower( $e['sigle'] ) ); ?>" data-ville="<?php echo esc_attr( $e['ville'] ); ?>" style="--etab:<?php echo esc_attr( $e['couleur'] ); ?>">
					<figure class="etab__photo etab__photo--g"><img src="<?php echo esc_url( $e['photo_g'] ); ?>" alt="" loading="lazy"></figure>
					<div class="etab__corps">
						<div class="etab__entete">
							<img class="etab__logo" src="<?php echo esc_url( $e['logo'] ); ?>" alt="" width="80" height="80" loading="lazy">
							<div>
								<p class="etab__sigle"><?php echo esc_html( $e['sigle'] ); ?> <span><?php echo sueb_icone( 'lieu', 14 ); ?><?php echo esc_html( $e['ville'] ); ?></span></p>
								<h3 class="etab__nom"><a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['titre'] ); ?></a></h3>
							</div>
						</div>
						<?php if ( $e['enfants'] ) : ?>
							<ul class="etab__deps">
								<?php foreach ( $e['enfants'] as $d ) : ?>
									<li><button type="button" data-explorer="<?php echo (int) $e['id']; ?>" data-chemin="<?php echo (int) $d['id']; ?>"><?php echo esc_html( $d['titre'] ); ?></button></li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p class="etab__vide">Départements en cours de publication.</p>
						<?php endif; ?>
						<?php if ( ! empty( $e['provisoire'] ) ) : ?>
							<div class="etab__pied">
								<a class="btn btn--etab" href="<?php echo esc_url( SUEB_LIENS['preinscription'] ); ?>">Préinscription en ligne<?php echo sueb_icone( 'fleche', 16 ); ?></a>
							</div>
						<?php else : ?>
							<div class="etab__pied">
								<p class="etab__compte"><span><strong><?php echo (int) $nb_dep; ?></strong> département<?php echo $nb_dep > 1 ? 's' : ''; ?></span><span><strong><?php echo (int) $nb_fil; ?></strong> filière<?php echo $nb_fil > 1 ? 's' : ''; ?></span><span><strong><?php echo (int) $nb_ue; ?></strong> UE</span></p>
								<button class="btn btn--etab" type="button" data-explorer="<?php echo (int) $e['id']; ?>">Explorer la formation<?php echo sueb_icone( 'fleche', 16 ); ?></button>
							</div>
						<?php endif; ?>
					</div>
					<figure class="etab__photo etab__photo--d"><img src="<?php echo esc_url( $e['photo_d'] ); ?>" alt="" loading="lazy"></figure>
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