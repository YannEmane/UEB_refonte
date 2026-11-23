<?php
/**
 * La page Académie (/academie/, archive des structures) : l'offre de formation
 * par parcours, pas à pas.
 *
 *  1. /academie/ : les parcours (classique, professionnel, BTS et ingénierie, médecine) ;
 *  2. /academie/parcours/classique/ : les établissements qui proposent ce parcours ;
 *  3. /academie/parcours/classique/fs/ : ses départements dans cet établissement ;
 *  4. /academie/parcours/classique/fs/<departement>/ : les filières du département ;
 *     une filière ouvre sa page (niveaux, unités d'enseignement, enseignants).
 *
 * Un établissement dont les filières ne sont pas rangées par département (un seul
 * groupe « Filières ») passe directement de l'étape 2 aux filières.
 *
 * L'entrée par établissement (menu Académie de l'en-tête) prend les mêmes étapes
 * dans l'autre ordre :
 *
 *  1. /academie/fs/ : les formations de l'établissement (classique, professionnelle…) ;
 *  2. /academie/fs/classique/ : les départements de cette formation ;
 *  3. /academie/fs/classique/<departement>/ : les filières du département.
 *
 * Les parcours et leur rangement : inc/parcours.php.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$tous  = sueb_parcours();
$offre = sueb_offre_par_parcours();
list( $slug, $sigle, $dep_slug ) = sueb_parcours_demande();
$par_etab = sueb_academie_par_etab();                          // entrée par l'établissement plutôt que par le parcours
$parcours = $tous[ $slug ] ?? null;
$choix    = $parcours && $sigle ? ( $offre[ $slug ][ $sigle ] ?? null ) : null;
$etab     = $choix ? $choix['etab'] : ( $par_etab && $sigle ? sueb_etab_par_sigle( $sigle ) : null );
$par_dep  = $choix && sueb_parcours_par_departement( $choix ); // l'étape « département » a-t-elle lieu d'être ?
$dep      = $choix && $dep_slug ? ( $choix['departements'][ $dep_slug ] ?? null ) : null;
$liste_dep = $par_dep && ! $dep;                               // on en est au choix du département
$filieres = $dep ? $dep['filieres'] : ( $choix ? $choix['filieres'] : array() );

/* Entrée par l'établissement, première étape : ses formations, dans l'ordre des parcours (parcours => choix). */
$accueil_etab = $par_etab && $etab && ! $parcours;
$formations   = array();
if ( $accueil_etab ) {
	foreach ( $offre as $cle => $par_sigle ) {
		if ( isset( $par_sigle[ $sigle ] ) ) {
			$formations[ $cle ] = $par_sigle[ $sigle ];
		}
	}
}

/* L'adresse d'une étape suivante, dans l'ordre par lequel le visiteur est entré. */
$url = function ( $p, $s, $d = '' ) use ( $par_etab ) {
	return $par_etab ? sueb_url_formation( $s, $p, $d ) : sueb_url_parcours( $p, $s, $d );
};

/*
 * Les diplômes réellement préparés dans une formation, déduits des niveaux de ses filières dans ce parcours
 * (BTS 1 donne « BTS », L2 pro « Licence professionnelle », 2nd cycle « DIPET II », Niveau 4 « Diplôme d’ingénieur »),
 * à défaut de leur diplôme ; dans l'ordre des études. Sans rien de reconnu : les diplômes du parcours.
 */
$diplomes = function ( array $filieres, array $par_defaut ) {
	$motifs = array( // motif sur le niveau => diplôme, dans l'ordre des études
		'/^bts/iu'                    => 'BTS',
		'/^L\s?\d\s*pro$|^LP\s?\d$/iu' => 'Licence professionnelle',
		'/^L\s?\d$/iu'                => 'Licence',
		'/^M\s?\d\s*pro$|^MP\s?\d$/iu' => 'Master professionnel',
		'/^M\s?\d$/iu'                => 'Master',
		'/^1er cycle/iu'              => 'DIPET I',
		'/^2nd cycle/iu'              => 'DIPET II',
		'/^niveau\s*\d/iu'            => 'Diplôme d’ingénieur',
	);
	$ordre = array_flip( array_values( $motifs ) );
	$liste = array();
	foreach ( $filieres as $f ) {
		$trouve = false;
		foreach ( $f['niveaux'] as $n ) {
			foreach ( $motifs as $motif => $diplome ) {
				if ( preg_match( $motif, trim( $n ) ) ) {
					$liste[ $diplome ] = $ordre[ $diplome ];
					$trouve            = true;
					break;
				}
			}
		}
		if ( ! $trouve && preg_match( '/^(doctorat en \S+)/iu', $f['diplome'], $m ) ) { // médecine, pharmacie
			$liste[ $m[1] ] = 90 + count( $liste );
		} elseif ( ! $trouve && preg_match( '/^(licence professionnelle|master professionnel|bts|dipet)/iu', $f['diplome'], $m ) ) {
			$d            = 'bts' === mb_strtolower( $m[1] ) ? 'BTS' : ( 'dipet' === mb_strtolower( $m[1] ) ? 'DIPET I' : ucfirst( mb_strtolower( $m[1] ) ) );
			$liste[ $d ] = $ordre[ $d ];
		}
	}
	asort( $liste );
	return $liste ? array_keys( $liste ) : $par_defaut;
};

/* Le nombre d'unités d'enseignement de chaque filière affichée, en une requête. */
$nb_ue = array();
if ( $filieres && ! $liste_dep ) {
	global $wpdb;
	$ids = implode( ',', array_map( 'intval', array_column( $filieres, 'id' ) ) );
	foreach ( $wpdb->get_results( "SELECT post_parent, COUNT(*) AS n FROM {$wpdb->posts} WHERE post_type = 'ueb_structure' AND post_status = 'publish' AND post_parent IN ($ids) GROUP BY post_parent" ) as $l ) { // phpcs:ignore WordPress.DB -- identifiants entiers
		$nb_ue[ (int) $l->post_parent ] = (int) $l->n;
	}
}

/* Les établissements d'un parcours, dans l'ordre de l'accueil : facultés, écoles, institut. */
$ranger = function ( array $par_sigle ) {
	$ordre = array_flip( array( 'FS', 'FSJP', 'FALSH', 'FSEG', 'FMSP', 'ENSET', 'ESTLC', 'ENSTMO', 'ISABEE' ) );
	uksort( $par_sigle, function ( $a, $b ) use ( $ordre ) {
		return ( $ordre[ $a ] ?? 99 ) <=> ( $ordre[ $b ] ?? 99 );
	} );
	return $par_sigle;
};
$compter = function ( array $par_sigle ) {
	return count( array_unique( array_merge( array(), ...array_values( array_map( function ( $c ) {
		return array_column( $c['filieres'], 'id' );
	}, $par_sigle ) ?: array( array() ) ) ) ) );
};
$pluriel = function ( $n, $un, $plusieurs ) {
	return $n . ' ' . ( $n > 1 ? $plusieurs : $un );
};

get_header();
?>
<header class="acad-tete<?php echo $etab ? ' acad-tete--etab' : ''; ?>"<?php if ( $etab ) : ?> style="--etab:<?php echo esc_attr( $etab['couleur'] ); ?>"<?php elseif ( $parcours ) : ?> style="--etab:<?php echo esc_attr( $parcours['couleur'] ); ?>"<?php endif; ?>>
	<div class="conteneur">
		<ol class="fil-ariane">
			<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li>
			<?php if ( $par_etab ) : ?>
				<li><a href="<?php echo esc_url( sueb_url_academie() ); ?>">Académie</a></li>
				<?php if ( ! $parcours ) : ?>
					<li aria-current="page"><?php echo esc_html( $sigle ); ?></li>
				<?php else : ?>
					<li><a href="<?php echo esc_url( sueb_url_formation( $sigle ) ); ?>"><?php echo esc_html( $sigle ); ?></a></li>
					<?php if ( $dep && $par_dep ) : ?>
						<li><a href="<?php echo esc_url( sueb_url_formation( $sigle, $slug ) ); ?>"><?php echo esc_html( $parcours['nom'] ); ?></a></li>
						<li aria-current="page"><?php echo esc_html( $dep['sigle'] ?: $dep['titre'] ); ?></li>
					<?php else : ?>
						<li aria-current="page"><?php echo esc_html( $parcours['nom'] ); ?></li>
					<?php endif; ?>
				<?php endif; ?>
			<?php elseif ( ! $parcours ) : ?>
				<li aria-current="page">Académie</li>
			<?php else : ?>
				<li><a href="<?php echo esc_url( sueb_url_academie() ); ?>">Académie</a></li>
				<?php if ( $etab ) : ?>
					<li><a href="<?php echo esc_url( sueb_url_parcours( $slug ) ); ?>"><?php echo esc_html( $parcours['nom'] ); ?></a></li>
					<?php if ( $dep && $par_dep ) : ?>
						<li><a href="<?php echo esc_url( sueb_url_parcours( $slug, $sigle ) ); ?>"><?php echo esc_html( $sigle ); ?></a></li>
						<li aria-current="page"><?php echo esc_html( $dep['sigle'] ?: $dep['titre'] ); ?></li>
					<?php else : ?>
						<li aria-current="page"><?php echo esc_html( $sigle ); ?></li>
					<?php endif; ?>
				<?php else : ?>
					<li aria-current="page"><?php echo esc_html( $parcours['nom'] ); ?></li>
				<?php endif; ?>
			<?php endif; ?>
		</ol>
		<div class="acad-tete__ligne">
			<?php if ( $accueil_etab ) : ?>
				<div>
					<?php
					$le   = sueb_le_sigle( $sigle, $etab['titre'] );
					$nb_f = $compter( $formations );
					if ( $formations ) {
						$dans  = array_map( function ( $cle ) use ( $tous ) {
							return $tous[ $cle ]['dans'];
						}, array_keys( $formations ) );
						$dans  = count( $dans ) > 1 ? implode( ', ', array_slice( $dans, 0, -1 ) ) . ' et ' . end( $dans ) : $dans[0];
						$suite = array_filter( $formations, 'sueb_parcours_par_departement' ) ? 'ses départements, puis ses filières et leurs unités d’enseignement' : 'ses filières et leurs unités d’enseignement';
						$chapo = sprintf( '%s propose %s, %s. Choisissez une formation pour voir %s.', ucfirst( $le ), $pluriel( $nb_f, 'filière', 'filières' ), $dans, $suite );
					} else {
						$chapo = sprintf( 'Les formations %s seront bientôt publiées.', sueb_de_sigle( $sigle, $etab['titre'] ) );
					}
					?>
					<h1 class="acad-tete__titre acad-tete__titre--etab"><?php echo esc_html( $etab['titre'] ); ?></h1>
					<p class="acad-tete__chapo"><?php echo esc_html( $chapo ); ?></p>
				</div>
				<img class="acad-tete__logo" src="<?php echo esc_url( $etab['logo'] ); ?>" alt="" width="96" height="96">
			<?php elseif ( $etab ) : ?>
				<div>
					<?php
					$ou = $etab['ville'] ? ', à ' . $etab['ville'] : '';
					if ( $liste_dep ) {
						$chapo = sprintf( '%s %s%s. Choisissez un département pour voir ses filières.', $pluriel( count( $choix['departements'] ), 'département propose des filières', 'départements proposent des filières' ), $parcours['dans'], $ou );
					} else {
						$chapo = sprintf( '%s %s%s. Choisissez une filière pour découvrir ses niveaux et ses unités d’enseignement.', $pluriel( count( $filieres ), 'filière', 'filières' ), $parcours['dans'], $ou );
					}
					?>
					<p class="acad-tete__sur"><?php echo esc_html( $parcours['nom'] . ( $dep && $par_dep ? ' · ' . $etab['titre'] : '' ) ); ?></p>
					<h1 class="acad-tete__titre acad-tete__titre--etab"><?php echo esc_html( $dep && $par_dep ? $dep['titre'] : $etab['titre'] ); ?></h1>
					<p class="acad-tete__chapo"><?php echo esc_html( $chapo ); ?></p>
				</div>
				<img class="acad-tete__logo" src="<?php echo esc_url( $etab['logo'] ); ?>" alt="" width="96" height="96">
			<?php elseif ( $parcours ) : ?>
				<div>
					<p class="acad-tete__sur">Parcours</p>
					<h1 class="acad-tete__titre"><?php echo esc_html( $parcours['nom'] ); ?></h1>
					<p class="acad-tete__chapo"><?php echo esc_html( $parcours['texte'] ); ?></p>
				</div>
				<dl class="acad-chiffres">
					<div><dt><?php echo count( $offre[ $slug ] ) > 1 ? 'établissements' : 'établissement'; ?></dt><dd><?php echo count( $offre[ $slug ] ); ?></dd></div>
					<div><dt><?php echo $compter( $offre[ $slug ] ) > 1 ? 'filières' : 'filière'; ?></dt><dd><?php echo (int) $compter( $offre[ $slug ] ); ?></dd></div>
				</dl>
			<?php else : ?>
				<div>
					<h1 class="acad-tete__titre">Académie</h1>
					<p class="acad-tete__chapo">Choisissez un parcours, puis l’établissement qui le propose : vous y trouverez ses filières, jusqu’aux unités d’enseignement et à leurs enseignants.</p>
				</div>
				<dl class="acad-chiffres">
					<div><dt>parcours</dt><dd><?php echo count( $tous ); ?></dd></div>
					<div><dt>établissements</dt><dd><?php echo count( sueb_etablissements() ); ?></dd></div>
					<div><dt>filières</dt><dd><?php echo (int) array_sum( array_map( function ( $e ) { return sueb_compter( $e, 2 ); }, sueb_arbre_formation() ) ); ?></dd></div>
				</dl>
			<?php endif; ?>
		</div>

		<?php
		/* Les étapes : parcours, établissement, département (s'il y a lieu), filière. Chacune : libellé, lien une fois franchie.
		   Par l'établissement : établissement, formation, département (s'il y a lieu), filière. */
		if ( $par_etab ) {
			$etapes = array(
				array( $sigle, sueb_url_academie() . '#par-etablissement' ),
				array( $parcours ? $parcours['court'] : 'La formation', $parcours ? sueb_url_formation( $sigle ) : '' ),
			);
			if ( ! $parcours || $par_dep ) {
				$etapes[] = array( $dep ? ( $dep['sigle'] ?: 'Le département' ) : 'Le département', $dep ? sueb_url_formation( $sigle, $slug ) : '' );
			}
			$etapes[] = array( 'La filière', '' );
			$courante = ! $parcours ? 1 : ( $liste_dep ? 2 : count( $etapes ) - 1 );
		} else {
			$etapes = array(
				array( $parcours ? $parcours['court'] : 'Le parcours', $parcours ? sueb_url_academie() : '' ),
				array( $etab ? $sigle : 'L’établissement', $etab ? sueb_url_parcours( $slug ) : '' ),
			);
			if ( ! $etab || $par_dep ) {
				$etapes[] = array( $dep ? ( $dep['sigle'] ?: 'Le département' ) : 'Le département', $dep ? sueb_url_parcours( $slug, $sigle ) : '' );
			}
			$etapes[] = array( 'La filière', '' );
			$courante = ! $parcours ? 0 : ( ! $etab ? 1 : ( $liste_dep ? 2 : count( $etapes ) - 1 ) );
		}
		?>
		<ol class="acad-etapes" aria-label="Étapes">
			<?php foreach ( $etapes as $k => $e ) : ?>
				<li class="<?php echo $k < $courante ? 'est-faite' : ( $k === $courante ? 'est-courante' : '' ); ?>"<?php echo $k === $courante ? ' aria-current="step"' : ''; ?>>
					<?php if ( $k < $courante && $e[1] ) : ?><a href="<?php echo esc_url( $e[1] ); ?>"><span><?php echo (int) $k + 1; ?></span> <?php echo esc_html( $e[0] ); ?></a><?php else : ?><span><?php echo (int) $k + 1; ?></span> <?php echo esc_html( $e[0] ); ?><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</header>

<?php if ( $accueil_etab ) : ?>

	<section class="acad-corps" aria-labelledby="titre-formations" style="--etab:<?php echo esc_attr( $etab['couleur'] ); ?>">
		<div class="conteneur">
			<header class="acad-famille__tete">
				<h2 id="titre-formations">Formations <span><?php echo count( $formations ); ?></span></h2>
			</header>
			<?php if ( $formations ) : ?>
				<?php /* Les cartes des parcours, comme sur la page Académie (sans flèche, à la demande de l'utilisateur). */ ?>
				<ol class="parcours">
					<?php $i = 0; foreach ( $formations as $cle => $c ) :
						$p      = $tous[ $cle ];
						$nb_dep = sueb_parcours_par_departement( $c ) ? count( $c['departements'] ) : 0;
						$compte = ( $nb_dep ? $pluriel( $nb_dep, 'département', 'départements' ) . ', ' : '' ) . $pluriel( count( $c['filieres'] ), 'filière', 'filières' );
						?>
						<li class="parcours__carte<?php echo $i === count( $formations ) - 1 && count( $formations ) % 2 ? ' parcours__carte--large' : ''; ?> revele" style="--parcours:<?php echo esc_attr( $p['couleur'] ); ?>;--d:<?php echo esc_attr( round( $i++ * .07, 2 ) ); ?>s">
							<a class="parcours__lien" href="<?php echo esc_url( sueb_url_formation( $sigle, $cle ) ); ?>">
								<span class="parcours__haut">
									<span class="parcours__icone"><?php echo sueb_icone( $p['icone'], 28 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG du thème ?></span>
								</span>
								<span class="parcours__nom"><?php echo esc_html( $p['nom'] ); ?></span>
								<span class="parcours__texte"><?php echo esc_html( $p['texte'] ); ?></span>
								<span class="parcours__diplomes">
									<?php foreach ( $diplomes( $c['filieres'], $p['diplomes'] ) as $d ) : ?><span><?php echo esc_html( $d ); ?></span><?php endforeach; ?>
								</span>
								<span class="parcours__pied">
									<span class="parcours__compte"><?php echo esc_html( $compte ); ?></span>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php else : ?>
				<p class="vide">Aucune filière de cet établissement n’est encore publiée.</p>
			<?php endif; ?>

			<nav class="acad-autres" aria-label="<?php echo esc_attr( 'Autres pages ' . sueb_de_sigle( $sigle, $etab['titre'] ) ); ?>">
				<span><?php echo esc_html( $sigle ); ?></span>
				<?php if ( $etab['id'] > 0 ) : ?><a href="<?php echo esc_url( get_permalink( $etab['id'] ) ); ?>">La page de l’établissement</a><?php endif; ?>
				<a href="<?php echo esc_url( sueb_url_academie() ); ?>">Tous les parcours de l’université</a>
			</nav>
		</div>
	</section>

<?php elseif ( ! $parcours ) : ?>

	<section class="acad-corps" aria-labelledby="titre-parcours">
		<div class="conteneur">
			<h2 class="sr" id="titre-parcours">Les parcours de formation</h2>
			<ol class="parcours">
				<?php $i = 0; foreach ( $tous as $cle => $p ) :
					$nb_etab = count( $offre[ $cle ] );
					$nb_fil  = $compter( $offre[ $cle ] );
					?>
					<li class="parcours__carte revele" style="--parcours:<?php echo esc_attr( $p['couleur'] ); ?>;--d:<?php echo esc_attr( round( $i * .07, 2 ) ); ?>s">
						<?php if ( $nb_fil ) : ?><a class="parcours__lien" href="<?php echo esc_url( sueb_url_parcours( $cle ) ); ?>"><?php else : ?><div class="parcours__lien"><?php endif; ?>
							<span class="parcours__haut">
								<span class="parcours__icone"><?php echo sueb_icone( $p['icone'], 28 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG du thème ?></span>
								<span class="parcours__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ++$i, 2, '0', STR_PAD_LEFT ) ); ?></span>
							</span>
							<span class="parcours__nom"><?php echo esc_html( $p['nom'] ); ?></span>
							<span class="parcours__texte"><?php echo esc_html( $p['texte'] ); ?></span>
							<span class="parcours__diplomes">
								<?php foreach ( $p['diplomes'] as $d ) : ?><span><?php echo esc_html( $d ); ?></span><?php endforeach; ?>
							</span>
							<span class="parcours__pied">
								<span class="parcours__compte"><?php echo $nb_fil ? esc_html( $pluriel( $nb_etab, 'établissement', 'établissements' ) . ' · ' . $pluriel( $nb_fil, 'filière', 'filières' ) ) : 'Filières bientôt publiées'; ?></span>
								<?php if ( $nb_fil ) : ?><span class="parcours__fleche" aria-hidden="true"><?php echo sueb_icone( 'fleche', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG du thème ?></span><?php endif; ?>
							</span>
						<?php echo $nb_fil ? '</a>' : '</div>'; ?>
					</li>
				<?php endforeach; ?>
			</ol>
			<nav class="acad-autres acad-autres--etabs" id="par-etablissement" aria-label="Commencer par l’établissement">
				<span>Ou par établissement</span>
				<?php foreach ( sueb_etablissements() as $s => $e ) : ?>
					<a href="<?php echo esc_url( sueb_url_formation( $s ) ); ?>" title="<?php echo esc_attr( $e['fr'] ); ?>"><img src="<?php echo esc_url( sueb_logo( $s ) ); ?>" alt="" width="24" height="24" loading="lazy"><?php echo esc_html( $s ); ?><span class="sr">, <?php echo esc_html( $e['fr'] ); ?></span></a>
				<?php endforeach; ?>
			</nav>
		</div>
	</section>

<?php elseif ( ! $etab ) : ?>

	<section class="acad-corps" aria-labelledby="titre-etabs">
		<div class="conteneur">
			<header class="acad-famille__tete">
				<h2 id="titre-etabs">Où suivre ce parcours <span><?php echo count( $offre[ $slug ] ); ?></span></h2>
				<p>Choisissez un établissement pour voir ses départements et ses filières <?php echo esc_html( $parcours['dans'] ); ?>.</p>
			</header>
			<ul class="acad-grille">
				<?php $i = 0; foreach ( $ranger( $offre[ $slug ] ) as $s => $c ) :
					$e        = $c['etab'];
					$etudiant = sueb_photo_etudiant( $s, $e['id'] > 0 ? $e['id'] : 0 );
					$apercu   = array_slice( array_column( $c['filieres'], 'titre' ), 0, 3 );
					?>
					<li class="acad-carte revele" style="--etab:<?php echo esc_attr( $e['couleur'] ); ?>;--sur-etab:<?php echo esc_attr( sueb_texte_sur( $e['couleur'] ) ); ?>;--d:<?php echo esc_attr( round( ( $i++ % 3 ) * .07, 2 ) ); ?>s">
						<div class="acad-carte__bandeau">
							<span class="acad-carte__logo"><img src="<?php echo esc_url( $e['logo'] ); ?>" alt="" width="56" height="56" loading="lazy"></span>
							<p class="acad-carte__sigle"><?php echo esc_html( $s ); ?></p>
							<?php if ( $etudiant ) : ?><img class="acad-carte__etudiant" src="<?php echo esc_url( $etudiant ); ?>" alt="" loading="lazy"><?php endif; ?>
						</div>
						<div class="acad-carte__corps">
							<h3 class="acad-carte__nom"><a href="<?php echo esc_url( sueb_url_parcours( $slug, $s ) ); ?>"><?php echo esc_html( $e['titre'] ); ?></a></h3>
							<?php if ( $e['ville'] ) : ?><p class="acad-carte__infos"><span><?php echo sueb_icone( 'lieu', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG du thème ?> <?php echo esc_html( $e['ville'] ); ?></span></p><?php endif; ?>
							<p class="acad-carte__resume"><?php echo esc_html( implode( ', ', $apercu ) . ( count( $c['filieres'] ) > count( $apercu ) ? '…' : '.' ) ); ?></p>
							<p class="acad-carte__pied">
								<span class="acad-carte__compte"><?php echo esc_html( $pluriel( count( $c['filieres'] ), 'filière', 'filières' ) ); ?></span>
								<span class="acad-carte__aller" aria-hidden="true"><?php echo sueb_icone( 'fleche', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG du thème ?></span>
							</p>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>

			<nav class="acad-autres" aria-label="Les autres parcours">
				<span>Autres parcours</span>
				<?php foreach ( $tous as $cle => $p ) : if ( $cle !== $slug && $offre[ $cle ] ) : ?>
					<a href="<?php echo esc_url( sueb_url_parcours( $cle ) ); ?>"><?php echo esc_html( $p['court'] ); ?></a>
				<?php endif; endforeach; ?>
			</nav>
		</div>
	</section>

<?php elseif ( $liste_dep ) : ?>

	<?php
	/* « Département de Chimie appliquée » devient « Chimie appliquée » : le titre de la rubrique dit déjà « Départements ». */
	$nom_dep = function ( $titre ) {
		$nom = preg_replace( '/^d[ée]partement\s+(?:de\s+la\s+|de\s+l[’\']|des\s+|du\s+|de\s+|d[’\'])/iu', '', $titre );
		return $nom === $titre ? $titre : mb_strtoupper( mb_substr( $nom, 0, 1 ) ) . mb_substr( $nom, 1 );
	};
	/* Une filière : intitulé (lien vers sa page et ses UE), diplôme, niveaux. */
	$ligne_filiere = function ( array $f ) {
		?>
		<li class="deps-fil">
			<a class="deps-fil__nom" href="<?php echo esc_url( $f['url'] ); ?>"><?php echo esc_html( $f['titre'] ); ?></a>
			<?php if ( $f['diplome'] ) : ?><span class="deps-fil__diplome"><?php echo esc_html( $f['diplome'] ); ?></span><?php endif; ?>
			<?php if ( $f['niveaux'] ) : ?>
				<span class="cursus__niveaux deps-fil__niveaux" aria-label="<?php echo esc_attr( 'Niveaux : ' . implode( ', ', array_map( 'sueb_nom_niveau', $f['niveaux'] ) ) ); ?>">
					<?php foreach ( $f['niveaux'] as $n ) : ?><span class="est-plein" title="<?php echo esc_attr( sueb_nom_niveau( $n ) ); ?>"><?php echo esc_html( preg_replace( '/^Niveau (\d+)$/', 'N$1', $n ) ); ?></span><?php endforeach; ?>
				</span>
			<?php endif; ?>
		</li>
		<?php
	};
	?>
	<section class="acad-corps" aria-labelledby="titre-deps" style="--etab:<?php echo esc_attr( $etab['couleur'] ); ?>">
		<div class="conteneur">
			<header class="acad-famille__tete">
				<h2 id="titre-deps">Départements <span><?php echo count( $choix['departements'] ); ?></span></h2>
			</header>

			<?php /* D'après le modèle fourni par l'utilisateur (2026-11-23) : des bandeaux décalés tour à tour, un onglet de couleur au bout ; un clic ouvre les filières du département. */ ?>
			<ol class="deps-onglets">
				<?php $i = 0; foreach ( $choix['departements'] as $adresse => $d ) :
					$noms = array_column( $d['filieres'], 'titre' );
					?>
					<li class="deps-onglet revele <?php echo 0 === $i % 2 ? 'revele--droite' : 'revele--gauche'; ?>" style="--d:<?php echo esc_attr( min( $i, 8 ) * .06 ); ?>s">
						<details class="deps-onglet__pli">
							<summary class="deps-onglet__bande">
								<span class="deps-onglet__num"><span aria-hidden="true"><?php echo esc_html( str_pad( (string) ++$i, 2, '0', STR_PAD_LEFT ) ); ?></span><?php if ( $d['sigle'] ) : ?><small><?php echo esc_html( $d['sigle'] ); ?></small><?php endif; ?></span>
								<span class="deps-onglet__icone"><?php echo sueb_icone( sueb_icone_discipline( $d['titre'] ), 34 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG du thème ?></span>
								<span class="deps-onglet__texte">
									<span class="deps-onglet__nom"><?php echo esc_html( $nom_dep( $d['titre'] ) ); ?></span>
									<span class="deps-onglet__resume"><?php echo esc_html( $pluriel( count( $noms ), 'filière', 'filières' ) ); ?><span class="deps-onglet__liste"><?php echo esc_html( ' : ' . implode( ', ', $noms ) . '.' ); ?></span></span>
								</span>
								<span class="deps-onglet__tab" aria-hidden="true"><span class="deps-onglet__signe"></span></span>
							</summary>
							<div class="deps-onglet__corps">
								<ul class="deps-fils"><?php array_map( $ligne_filiere, $d['filieres'] ); ?></ul>
								<?php if ( ! $d['groupe'] ) : ?><p class="deps-onglet__lien"><a class="lien-texte" href="<?php echo esc_url( get_permalink( $d['id'] ) ); ?>">Page du département</a></p><?php endif; ?>
							</div>
						</details>
					</li>
				<?php endforeach; ?>
			</ol>

			<nav class="acad-autres" aria-label="<?php echo esc_attr( 'Les autres parcours ' . $sigle ); ?>">
				<span><?php echo esc_html( $sigle ); ?></span>
				<?php foreach ( $tous as $cle => $p ) : if ( $cle !== $slug && isset( $offre[ $cle ][ $sigle ] ) ) : ?>
					<a href="<?php echo esc_url( $url( $cle, $sigle ) ); ?>"><?php echo esc_html( $p['court'] ); ?></a>
				<?php endif; endforeach; ?>
				<?php if ( $etab['id'] > 0 ) : ?><a href="<?php echo esc_url( get_permalink( $etab['id'] ) ); ?>">La page de l’établissement</a><?php endif; ?>
			</nav>
		</div>
	</section>

<?php else : ?>

	<section class="acad-corps" aria-labelledby="titre-filieres" style="--etab:<?php echo esc_attr( $etab['couleur'] ); ?>">
		<div class="conteneur">
			<header class="acad-famille__tete">
				<h2 id="titre-filieres">Filières <span><?php echo count( $filieres ); ?></span></h2>
				<p><?php echo esc_html( $parcours['nom'] . ' · ' . $sigle . ( $dep && $par_dep && $dep['sigle'] ? ' · ' . $dep['sigle'] : '' ) ); ?></p>
			</header>
			<ol class="cursus__liste">
				<?php foreach ( $filieres as $i => $f ) : ?>
					<li class="cursus__item revele" style="--d:<?php echo esc_attr( min( $i, 8 ) * .05 ); ?>s">
						<a class="cursus__lien" href="<?php echo esc_url( $f['url'] ); ?>">
							<span class="cursus__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<span class="cursus__corps">
								<span class="cursus__titre"><?php echo esc_html( $f['titre'] ); ?></span>
								<span class="cursus__meta">
									<?php if ( $f['code'] ) : ?><span class="cursus__code"><?php echo esc_html( $f['code'] ); ?></span><?php endif; ?>
									<?php if ( $f['diplome'] ) : ?><span><?php echo esc_html( $f['diplome'] ); ?></span><?php endif; ?>
									<?php if ( $f['departement'] && ! $dep ) : ?><span><?php echo esc_html( $f['departement'] ); ?></span><?php endif; ?>
									<span><?php echo empty( $nb_ue[ $f['id'] ] ) ? 'Unités d’enseignement à venir' : esc_html( $pluriel( $nb_ue[ $f['id'] ], 'unité d’enseignement', 'unités d’enseignement' ) ); ?></span>
								</span>
							</span>
							<?php if ( $f['niveaux'] ) : ?>
								<span class="cursus__niveaux" aria-label="<?php echo esc_attr( 'Niveaux : ' . implode( ', ', array_map( 'sueb_nom_niveau', $f['niveaux'] ) ) ); ?>">
									<?php foreach ( $f['niveaux'] as $n ) : ?><span class="est-plein" title="<?php echo esc_attr( sueb_nom_niveau( $n ) ); ?>"><?php echo esc_html( preg_replace( '/^Niveau (\d+)$/', 'N$1', $n ) ); ?></span><?php endforeach; ?>
								</span>
							<?php endif; ?>
							<span class="cursus__fleche" aria-hidden="true"><?php echo sueb_icone( 'fleche', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG du thème ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>

			<nav class="acad-autres" aria-label="<?php echo esc_attr( 'Les autres parcours ' . $sigle ); ?>">
				<span><?php echo esc_html( $sigle ); ?></span>
				<?php foreach ( $tous as $cle => $p ) : if ( $cle !== $slug && isset( $offre[ $cle ][ $sigle ] ) ) : ?>
					<a href="<?php echo esc_url( $url( $cle, $sigle ) ); ?>"><?php echo esc_html( $p['court'] ); ?></a>
				<?php endif; endforeach; ?>
				<?php if ( $dep && $par_dep ) : ?><a href="<?php echo esc_url( $url( $slug, $sigle ) ); ?>">Les autres départements</a><a href="<?php echo esc_url( get_permalink( $dep['id'] ) ); ?>">La page du département</a><?php endif; ?>
				<?php if ( $etab['id'] > 0 ) : ?><a href="<?php echo esc_url( get_permalink( $etab['id'] ) ); ?>">La page de l’établissement</a><?php endif; ?>
			</nav>
		</div>
	</section>

<?php endif; ?>

<div class="conteneur acad-pied">
	<aside class="acad-appel revele">
		<div>
			<h2>Rejoindre l’Université d’Ebolowa</h2>
			<p>La préinscription se fait en ligne, pour tous les établissements.</p>
		</div>
		<a class="btn btn--or" href="<?php echo esc_url( sueb_lien( 'preinscription' ) ); ?>">Préinscription en ligne</a>
	</aside>
</div>
<?php
get_footer();
