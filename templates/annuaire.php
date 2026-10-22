<?php
/**
 * L'annuaire du personnel de l'UEb (page /personnel/, archive-ueb_personnel.php), à la manière des grands sites
 * institutionnels (GOV.UK, Commission européenne, Harvard, Stanford) : une barre
 * de recherche qui reste sous l'en-tête (nom ou fonction, établissement,
 * catégorie), puis les membres groupés par catégorie, en fiches compactes
 * (sueb_fiche_personnel). La direction de l'université y est rangée pôle par
 * pôle, du Rectorat aux directions centrales (sueb_direction_par_pole).
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
		$etabs[ $m['etab'] ] = $m['etab_id'] ? $m['etab_nom'] . ' (' . $m['etab'] . ')' : 'Rectorat';
	}
}
?>
<section class="personnel annuaire" id="annuaire" aria-label="Annuaire du personnel" data-annuaire>
	<?php if ( $total > 1 ) : ?>
		<div class="annuaire__barre">
			<div class="conteneur annuaire__barre-int" role="search" aria-label="Rechercher dans l’annuaire">
				<label class="annuaire__recherche">
					<span class="sr">Rechercher un membre du personnel</span>
					<?php echo sueb_icone( 'recherche', 20 ); ?>
					<input type="search" placeholder="Rechercher un nom ou une fonction" autocomplete="off" data-annuaire-recherche>
				</label>
				<?php if ( count( $etabs ) > 1 ) : ?>
					<label class="annuaire__choix">
						<span class="sr">Établissement</span>
						<select data-annuaire-etab>
							<option value="">Tous les établissements</option>
							<?php foreach ( $etabs as $sigle => $nom ) : ?>
								<option value="<?php echo esc_attr( $sigle ); ?>"><?php echo esc_html( $nom ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>
				<?php if ( count( $groupes ) > 1 ) : ?>
					<div class="filtres annuaire__corps" role="group" aria-label="Catégorie de personnel" data-annuaire-corps>
						<button type="button" aria-pressed="true" data-corps="">Tout le personnel</button>
						<?php foreach ( $groupes as $g ) : ?>
							<button type="button" aria-pressed="false" data-corps="<?php echo esc_attr( $g['slug'] ); ?>"><?php echo esc_html( $g['nom'] ); ?></button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="conteneur annuaire__contenu">
		<?php if ( ! $groupes ) : ?>
			<p class="vide">Les fiches du personnel seront bientôt publiées.</p>
		<?php else : ?>
			<p class="annuaire__compte" aria-live="polite" data-annuaire-compte></p>

			<?php foreach ( $groupes as $g ) : ?>
				<?php
				$n = count( $g['membres'] );
				/* La direction se range par pôle ; les autres catégories forment un seul bloc. */
				$poles = 'direction' === $g['slug'] ? sueb_direction_par_pole( $g['membres'] ) : array( '' => array( 'nom' => '', 'membres' => $g['membres'] ) );
				$i     = 0;
				?>
				<section class="annuaire__groupe" aria-labelledby="groupe-<?php echo esc_attr( $g['slug'] ); ?>" data-groupe="<?php echo esc_attr( $g['slug'] ); ?>">
					<h2 class="annuaire__titre" id="groupe-<?php echo esc_attr( $g['slug'] ); ?>"><?php echo esc_html( $g['nom'] ); ?><small data-groupe-compte><?php echo (int) $n; ?></small></h2>
					<?php foreach ( $poles as $cle => $pole ) : ?>
						<div class="annuaire__pole"<?php echo $cle ? ' data-pole' : ''; ?>>
							<?php if ( $pole['nom'] ) : ?>
								<h3 class="annuaire__sous-titre"><?php echo esc_html( $pole['nom'] ); ?></h3>
							<?php endif; ?>
							<ul class="fiches">
								<?php foreach ( $pole['membres'] as $m ) : ?>
									<?php echo sueb_fiche_personnel( $m, $i++ >= $apercu ? 'est-replie' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
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
