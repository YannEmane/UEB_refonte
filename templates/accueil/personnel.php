<?php
/**
 * Personnel de l'UEb : un annuaire à portraits, à la manière des grands sites
 * institutionnels (GOV.UK, Commission européenne, Harvard, Stanford) : une
 * recherche (nom, fonction, cours), un filtre par établissement, les membres
 * groupés par catégorie, chacun avec son portrait, sa fonction et sa fiche.
 * Les chefs de département et les enseignants n'y figurent pas : ils sont sur la
 * page de leur établissement (templates/etablissement.php, rubrique Équipe).
 * Le filtrage se fait dans la page (site.js).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/* Les chefs de département et les enseignants sont présentés sur la page de leur établissement, pas ici. */
$groupes = array_values( array_filter( sueb_personnel_par_corps(), function ( $g ) {
	return $g['membres'] && ! in_array( $g['slug'], array( 'chefs-departement', 'enseignants' ), true );
} ) );
$apercu  = 12; // au-delà, une catégorie se déplie à la demande
$total   = 0;
$etabs   = array();
foreach ( $groupes as $g ) {
	$total += count( $g['membres'] );
	foreach ( $g['membres'] as $m ) {
		$etabs[ $m['etab'] ] = $m['etab_nom'];
	}
}
?>
<section class="section personnel" id="personnel" aria-labelledby="titre-perso" data-annuaire>
	<div class="conteneur">
		<header class="section__tete section__tete--ligne personnel__tete revele">
			<div>
				<p class="surtitre">Personnels de l’UEb</p>
				<h2 id="titre-perso">Celles et ceux qui font l’université</h2>
				<p class="section__chapo">Les doyens et directeurs des neuf établissements : retrouvez chacun, sa fonction et son établissement. Les chefs de département et les enseignants sont présentés sur la page de leur établissement.</p>
			</div>
			<?php if ( $total > 1 ) : ?>
			<div class="annuaire__outils" role="search">
				<label class="annuaire__recherche">
					<span class="sr">Rechercher un membre du personnel</span>
					<?php echo sueb_icone( 'recherche', 20 ); ?>
					<input type="search" placeholder="Nom ou fonction" autocomplete="off" data-annuaire-recherche>
				</label>
				<?php if ( count( $etabs ) > 1 ) : ?>
					<label class="annuaire__choix">
						<span class="sr">Établissement</span>
						<select data-annuaire-etab>
							<option value="">Tous les établissements</option>
							<?php foreach ( $etabs as $sigle => $nom ) : ?>
								<option value="<?php echo esc_attr( $sigle ); ?>"><?php echo esc_html( $sigle ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</header>

		<?php if ( ! $groupes ) : ?>
			<p class="vide">Les fiches du personnel seront bientôt publiées.</p>
		<?php else : ?>
			<?php if ( count( $groupes ) > 1 ) : ?>
				<div class="filtres annuaire__corps" role="group" aria-label="Catégorie de personnel" data-annuaire-corps>
					<button type="button" aria-pressed="true" data-corps="">Tout le personnel</button>
					<?php foreach ( $groupes as $g ) : ?>
						<button type="button" aria-pressed="false" data-corps="<?php echo esc_attr( $g['slug'] ); ?>"><?php echo esc_html( $g['nom'] ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<p class="annuaire__compte" aria-live="polite" data-annuaire-compte></p>

			<?php foreach ( $groupes as $g ) : $n = count( $g['membres'] ); ?>
				<section class="annuaire__groupe" aria-labelledby="groupe-<?php echo esc_attr( $g['slug'] ); ?>" data-groupe="<?php echo esc_attr( $g['slug'] ); ?>">
					<h3 class="annuaire__titre" id="groupe-<?php echo esc_attr( $g['slug'] ); ?>"><?php echo esc_html( $g['nom'] ); ?><small data-groupe-compte><?php echo (int) $n; ?></small></h3>
					<ul class="profils">
						<?php foreach ( $g['membres'] as $i => $m ) : ?>
							<?php echo sueb_carte_personnel( $m, $i >= $apercu ? 'est-replie' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?>
						<?php endforeach; ?>
					</ul>
					<?php if ( $n > $apercu ) : ?>
						<button class="annuaire__plus" type="button" data-annuaire-plus><?php echo esc_html( sprintf( 'Afficher les %d autres', $n - $apercu ) ); ?><?php echo sueb_icone( 'chevron', 18 ); ?></button>
					<?php endif; ?>
				</section>
			<?php endforeach; ?>

			<div class="annuaire__vide" data-annuaire-vide hidden>
				<p><strong>Aucun membre ne correspond à cette recherche.</strong> Vérifiez l’orthographe du nom, ou cherchez dans tous les établissements.</p>
				<button class="btn btn--plein" type="button" data-annuaire-effacer>Effacer la recherche</button>
			</div>
		<?php endif; ?>
	</div>
</section>
