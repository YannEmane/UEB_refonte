<?php
/**
 * Personnel de l'UEb : accordéon par catégorie (doyens et directeurs,
 * chefs de département, enseignants…), filtrable par établissement.
 * Chaque fiche : photo, nom, fonction, cours dispensés.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$groupes = sueb_personnel_par_corps();
$etabs   = array();
foreach ( $groupes as $g ) {
	foreach ( $g['membres'] as $m ) {
		$etabs[ $m['etab'] ] = true;
	}
}
ksort( $etabs );
?>
<section class="section personnel" id="personnel" aria-labelledby="titre-perso">
	<div class="conteneur personnel__grille">
		<div class="personnel__intro revele revele--gauche">
			<p class="surtitre">Personnels de l’UEb</p>
			<h2 id="titre-perso">Celles et ceux qui font l’université</h2>
			<p class="section__chapo">Doyens, directeurs, chefs de département et enseignants de chaque établissement : leur fonction et les cours qu’ils dispensent.</p>
			<?php if ( count( $etabs ) > 1 ) : ?>
				<label class="choix">
					<span>Établissement</span>
					<select data-filtre-personnel>
						<option value="">Tous les établissements</option>
						<?php foreach ( array_keys( $etabs ) as $e ) : ?>
							<option value="<?php echo esc_attr( $e ); ?>"><?php echo esc_html( $e ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>
		</div>

		<div class="accordeon revele revele--droite">
			<p class="accordeon__fil"><strong>Membres du personnel</strong> <span aria-hidden="true">/</span> Vous cherchez…</p>
			<?php if ( ! $groupes ) : ?>
				<p class="vide">Les fiches du personnel seront bientôt publiées.</p>
			<?php endif; ?>
			<?php foreach ( $groupes as $i => $g ) : ?>
				<details class="accordeon__item" name="personnel"<?php echo 0 === $i && $g['membres'] ? ' open' : ''; ?>>
					<summary><span><?php echo esc_html( $g['nom'] ); ?><small><?php echo count( $g['membres'] ); ?></small></span><?php echo $g['membres'] ? sueb_icone( 'chevron', 22 ) : ''; ?></summary>
					<div class="accordeon__contenu">
						<?php if ( ! $g['membres'] ) : ?>
							<p class="accordeon__vide">Fiches en cours de publication.</p>
						<?php else : ?>
							<ul class="membres">
								<?php foreach ( $g['membres'] as $m ) : ?>
									<li class="membre" data-etab="<?php echo esc_attr( $m['etab'] ); ?>">
										<div class="membre__photo">
											<?php if ( $m['photo'] ) : ?>
												<img src="<?php echo esc_url( $m['photo'] ); ?>" alt="" loading="lazy">
											<?php else : ?>
												<span class="portrait-vide" aria-hidden="true"><?php echo esc_html( sueb_initiales( $m['nom'] ) ); ?></span>
											<?php endif; ?>
										</div>
										<div class="membre__texte">
											<span class="membre__etab"><?php echo esc_html( $m['etab'] ); ?></span>
											<p class="membre__nom"><?php echo esc_html( $m['nom'] ); ?></p>
											<p class="membre__fonction"><?php echo esc_html( $m['fonction'] ); ?></p>
											<?php if ( $m['cours'] ) : ?><p class="membre__cours"><?php echo sueb_icone( 'livre', 15 ); ?><?php echo esc_html( $m['cours'] ); ?></p><?php endif; ?>
										</div>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
