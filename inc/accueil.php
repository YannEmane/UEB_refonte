<?php
/**
 * Données de la page d'accueil, lues dans les contenus de l'admin.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/** Une page par son identifiant d'URL (message-du-recteur, histoire…). */
function sueb_page( $slug ) {
	return get_page_by_path( $slug );
}

/** Les dirigeants du rectorat, dans l'ordre choisi dans l'admin. */
function sueb_dirigeants() {
	$officiels = sueb_fonctions_rectorat();
	$liste     = array();
	foreach ( get_posts( array( 'post_type' => 'ueb_dirigeant', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) as $p ) {
		$sigle   = (string) sueb_meta( $p->ID, 'sigle' );
		$liste[] = array(
			'id'       => $p->ID,
			'nom'      => $p->post_title,
			'sigle'    => 'RECTEUR' === $sigle ? '' : $sigle,
			'fonction' => sueb_meta( $p->ID, 'fonction' ) ?: ( $officiels[ $sigle ] ?? '' ),
			'photo'    => sueb_image_url( $p->ID, 'sueb-portrait' ),
			'recteur'  => 'RECTEUR' === $sigle,
		);
	}
	return $liste;
}

/**
 * L'offre de formation en arbre : établissements > départements >
 * filières > UE. Une seule requête, l'arbre est monté en mémoire.
 */
function sueb_arbre_formation() {
	$tous = get_posts( array( 'post_type' => 'ueb_structure', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
	$fixe = sueb_etablissements();
	$noeuds = array();
	foreach ( $tous as $p ) {
		$sigle = (string) sueb_meta( $p->ID, 'sigle' );
		$n     = array(
			'id'     => $p->ID,
			'parent' => $p->post_parent,
			'titre'  => $p->post_title,
			'sigle'  => $sigle,
			'url'    => get_permalink( $p ),
			'texte'  => wp_strip_all_tags( get_the_excerpt( $p ) ),
			'resp'   => (string) sueb_meta( $p->ID, 'responsable' ),
			'enfants'=> array(),
		);
		if ( 0 === $p->post_parent ) {
			$f            = $fixe[ strtoupper( $sigle ) ] ?? array( 'couleur' => '#163d2b', 'ville' => '', 'photos' => array( 'campus-ebolowa', 'amphi' ) );
			$logo         = (int) sueb_meta( $p->ID, 'logo' );
			$n['ville']   = sueb_meta( $p->ID, 'ville' ) ?: $f['ville'];
			$n['couleur'] = sueb_meta( $p->ID, 'couleur' ) ?: $f['couleur'];
			$n['logo']    = $logo ? wp_get_attachment_image_url( $logo, 'medium' ) : sueb_logo( $sigle );
			$n['photo_g'] = sueb_image_url( $p->ID, 'sueb-carte', '', $f['photos'][0] );
			$n['photo_d'] = sueb_image_url( $p->ID, 'sueb-carte', 'photo_droite', $f['photos'][1] );
		} else {
			$syllabus     = (int) sueb_meta( $p->ID, 'syllabus' );
			$n['code']    = (string) sueb_meta( $p->ID, 'code' );
			$n['credits'] = (string) sueb_meta( $p->ID, 'credits' );
			$n['semestre']= (string) sueb_meta( $p->ID, 'semestre' );
			$n['volume']  = (string) sueb_meta( $p->ID, 'volume' );
			$n['diplome'] = (string) sueb_meta( $p->ID, 'diplome' );
			$n['syllabus']= $syllabus ? (string) wp_get_attachment_url( $syllabus ) : '';
		}
		$noeuds[ $p->ID ] = $n;
	}

	/* Montage de l'arbre par références, des feuilles vers la racine. */
	$racines = array();
	foreach ( $noeuds as $id => &$n ) {
		if ( $n['parent'] && isset( $noeuds[ $n['parent'] ] ) ) {
			$noeuds[ $n['parent'] ]['enfants'][] = &$n;
		} elseif ( ! $n['parent'] ) {
			$racines[] = &$n;
		}
	}
	unset( $n );

	/* Les neuf établissements sont toujours présentés : une fiche créée dans l'admin remplace
	   leur carte provisoire (logo, couleur et photos du thème, identifiant négatif). */
	$presents = array();
	foreach ( $racines as $r ) {
		$presents[ strtoupper( $r['sigle'] ) ] = true;
	}
	$i = 0;
	foreach ( $fixe as $sigle => $f ) {
		--$i;
		if ( isset( $presents[ $sigle ] ) ) {
			continue;
		}
		$racines[] = array(
			'id'         => $i,
			'parent'     => 0,
			'titre'      => $f['fr'],
			'sigle'      => $sigle,
			'url'        => '#etab-' . strtolower( $sigle ),
			'texte'      => '',
			'resp'       => '',
			'enfants'    => array(),
			'ville'      => $f['ville'],
			'couleur'    => $f['couleur'],
			'logo'       => sueb_logo( $sigle ),
			'photo_g'    => sueb_photo( $f['photos'][0] ),
			'photo_d'    => sueb_photo( $f['photos'][1] ),
			'provisoire' => true,
		);
	}
	return $racines;
}

/** Nombre de structures d'un niveau sous un nœud (départements, filières, UE). */
function sueb_compter( array $noeud, $profondeur ) {
	if ( 0 === $profondeur ) {
		return 1;
	}
	$total = 0;
	foreach ( $noeud['enfants'] as $e ) {
		$total += sueb_compter( $e, $profondeur - 1 );
	}
	return $total;
}

/**
 * Les filières d'un partenaire sous tutelle, lues dans le champ « Filières sous tutelle »
 * (une par ligne, « FSEG : Banque » ou « FS, FSEG : Licences professionnelles »).
 * Chaque filière : son nom et les sigles des établissements de l'UEb qui en assurent la tutelle.
 */
function sueb_filieres_partenaire( $post_id ) {
	$filieres = array();
	foreach ( preg_split( '/\R/u', (string) sueb_meta( $post_id, 'filieres' ) ) as $ligne ) {
		$ligne = trim( $ligne );
		if ( '' === $ligne ) {
			continue;
		}
		$tutelles = array();
		if ( preg_match( '/^([A-Z][A-Za-z]*(?:\s*,\s*[A-Z][A-Za-z]*)*)\s*:\s*(.+)$/u', $ligne, $m ) ) {
			$tutelles = array_map( 'trim', explode( ',', $m[1] ) );
			$ligne    = trim( $m[2] );
		}
		$filieres[] = array( 'nom' => $ligne, 'tutelles' => $tutelles );
	}
	return $filieres;
}

/** Sigle tiré d'un nom, sans les petits mots (« Port Autonome de Kribi » donne PAK). */
function sueb_sigle_auto( $nom ) {
	$mots = preg_split( '/[\s\-’\']+/u', trim( $nom ), -1, PREG_SPLIT_NO_EMPTY );
	$mots = array_filter( $mots, function ( $m ) {
		return ! in_array( mb_strtolower( $m ), array( 'de', 'du', 'des', 'la', 'le', 'les', 'l', 'd', 'et', 'au', 'aux', 'en' ), true );
	} );
	$sigle = '';
	foreach ( array_slice( array_values( $mots ), 0, 4 ) as $m ) {
		$sigle .= mb_strtoupper( mb_substr( $m, 0, 1 ) );
	}
	return $sigle ?: 'UEb';
}

/**
 * Le réseau des partenaires, pour la carte et la liste : les universités et le
 * ministère (inc/config.php), puis les conventions saisies dans l'admin.
 * Chaque partenaire a une famille (enseignement supérieur, État et recherche,
 * entreprises, établissements sous tutelle), une ville (placée sur la carte si elle est connue), un logo ou
 * à défaut son sigle, et, pour une convention, sa date et son article.
 */
function sueb_reseau_partenaires() {
	$familles = array(
		'superieur'   => 'Enseignement supérieur',
		'etat'        => 'État et recherche',
		'entreprises' => 'Entreprises',
		'tutelle'     => 'Établissements sous tutelle',
	);
	$liste = array();
	foreach ( sueb_institutions_partenaires() as $i ) {
		$ministere = 'Convention' === $i['type'];
		$liste[]   = array(
			'id'      => 0,
			'nom'     => $i['nom'],
			'sigle'   => $i['sigle'],
			'famille' => $ministere ? 'etat' : 'superieur',
			'type'    => $ministere ? 'Ministère, convention' : 'Université publique',
			'ville'   => $i['ville'] ?? '',
			'logo'    => sueb_logo_partenaire( $i['id'] ),
			'date'    => '',
			'url'     => '',
			'texte'   => '',
			'tutelle' => '',
		);
	}
	foreach ( get_posts( array( 'post_type' => 'ueb_partenaire', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'DESC' ) ) ) as $p ) {
		$type    = (string) sueb_meta( $p->ID, 'type' );
		$sigle   = (string) sueb_meta( $p->ID, 'sigle' ) ?: sueb_sigle_auto( $p->post_title );
		$logo    = (int) sueb_meta( $p->ID, 'logo' );
		$famille = preg_match( '/tutelle/iu', $type ) ? 'tutelle' : ( preg_match( '/banque|entreprise|soci[ée]t[ée]/iu', $type ) ? 'entreprises' : ( preg_match( '/minist|recherche|institut de|agence|office/iu', $type ) ? 'etat' : 'superieur' ) );
		$liste[] = array(
			'id'      => $p->ID,
			'nom'     => $p->post_title,
			'sigle'   => $sigle,
			'famille' => $famille,
			'type'    => $type,
			'ville'   => (string) sueb_meta( $p->ID, 'ville' ),
			'logo'    => $logo ? (string) wp_get_attachment_image_url( $logo, 'medium' ) : ( sueb_logo_partenaire( $p->post_name ) ?: ( sueb_logo_partenaire( strtolower( $sigle ) ) ?: sueb_logo_partenaire( strtolower( $sigle ) . '-photo' ) ) ),
			'date'    => (string) sueb_meta( $p->ID, 'signature' ),
			'url'     => get_permalink( $p ),
			'texte'   => wp_strip_all_tags( get_the_excerpt( $p ) ),
			'tutelle' => (string) sueb_meta( $p->ID, 'tutelle' ),
		);
	}
	/* Une photo (fichier « …-photo ») remplit le médaillon ; un logo y est posé avec une marge. */
	foreach ( $liste as $k => $x ) {
		$liste[ $k ]['photo'] = (bool) preg_match( '/-photo[.-]/', basename( (string) $x['logo'] ) );
	}
	return array( 'familles' => $familles, 'partenaires' => $liste );
}

/** Les dernières actualités, avec leur format (article, vidéo, audio) et leur média. */
/*
 * Photos du thème pour les actualités sans image mise en avant (bannière et section « Nouvelles et évènements »),
 * une différente par position. Les recadrages d'une même photo (diplomes, vie-etudiante-1 et -12 = remise-toges ;
 * vie-etudiante-3 = equipe-fs ; bibliotheque = campus-ebolowa) sont écartés, de même que les plus petites images.
 */
function sueb_photos_repli() {
	return array( 'remise-toges', 'visite-port', 'equipe-fs', 'vie-etudiante-4', 'campus-ebolowa', 'salle', 'campus-batiments', 'campus-ambam' );
}

function sueb_actualites( $nombre = 7 ) {
	$liste = array();
	$repli = sueb_photos_repli();
	foreach ( get_posts( array( 'post_type' => 'post', 'numberposts' => $nombre ) ) as $i => $p ) {
		$format = get_post_format( $p ) ?: 'article';
		$media  = (int) sueb_meta( $p->ID, 'media' );
		$cats   = get_the_category( $p->ID );
		$liste[] = array(
			'titre'    => $p->post_title,
			'url'      => get_permalink( $p ),
			'date'     => get_the_date( 'j F Y', $p ),
			'jour'     => get_the_date( 'j', $p ),
			'mois'     => get_the_date( 'M Y', $p ),
			'iso'      => get_the_date( 'c', $p ),
			'texte'    => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $p ) ), 28 ),
			'photo'    => sueb_image_url( $p->ID, 'sueb-carte' ) ?: sueb_photo( $repli[ $i % count( $repli ) ] ),
			'format'   => in_array( $format, array( 'video', 'audio' ), true ) ? $format : 'article',
			'media'    => $media ? (string) wp_get_attachment_url( $media ) : '',
			'integre'  => sueb_video_integree( (string) sueb_meta( $p->ID, 'media_url' ) ),
			'duree'    => sueb_meta( $p->ID, 'duree' ),
			'rubrique' => $cats && 'uncategorized' !== $cats[0]->slug && 'non-classe' !== $cats[0]->slug ? $cats[0]->name : '',
		);
	}
	return $liste;
}

/** Les évènements à venir (puis les plus récents s'il n'y en a pas assez). */
function sueb_evenements( $nombre = 4 ) {
	$args = array(
		'post_type'   => 'ueb_evenement',
		'numberposts' => $nombre,
		'meta_key'    => '_sueb_debut',
		'orderby'     => 'meta_value',
		'order'       => 'ASC',
		'meta_query'  => array( array( 'key' => '_sueb_debut', 'value' => current_time( 'Y-m-d\TH:i' ), 'compare' => '>=' ) ),
	);
	$posts = get_posts( $args );
	if ( count( $posts ) < $nombre ) {
		unset( $args['meta_query'] );
		$args['order']       = 'DESC';
		$args['numberposts'] = $nombre - count( $posts );
		$args['exclude']     = wp_list_pluck( $posts, 'ID' );
		$posts               = array_merge( $posts, get_posts( $args ) );
	}
	$liste = array();
	foreach ( $posts as $p ) {
		$debut   = strtotime( (string) sueb_meta( $p->ID, 'debut' ) );
		$liste[] = array(
			'titre'  => $p->post_title,
			'url'    => get_permalink( $p ),
			'jour'   => $debut ? wp_date( 'j', $debut ) : '',
			'mois'   => $debut ? wp_date( 'M', $debut ) : '',
			'heure'  => $debut ? wp_date( 'G\hi', $debut ) : '',
			'iso'    => $debut ? wp_date( 'c', $debut ) : '',
			'passe'  => $debut && $debut < current_time( 'timestamp' ),
			'lieu'   => sueb_meta( $p->ID, 'lieu' ),
		);
	}
	return $liste;
}

/** Le personnel, groupé par catégorie (dans l'ordre des catégories). */
function sueb_personnel_par_corps() {
	$etabs = array();
	$fixes = sueb_etablissements();
	foreach ( get_posts( array( 'post_type' => 'ueb_structure', 'post_parent' => 0, 'numberposts' => -1 ) ) as $e ) {
		$sigle            = sueb_meta( $e->ID, 'sigle' ) ?: $e->post_title;
		$etabs[ $e->ID ] = array(
			'sigle'   => $sigle,
			'nom'     => $e->post_title,
			'couleur' => sueb_meta( $e->ID, 'couleur' ) ?: ( $fixes[ strtoupper( $sigle ) ]['couleur'] ?? '#163d2b' ),
		);
	}
	/* Dans une catégorie : l'ordre choisi dans l'admin, puis celui des établissements sur l'accueil. */
	$rang = array_flip( array( 'FS', 'FSJP', 'FALSH', 'FSEG', 'FMSP', 'ENSET', 'ESTLC', 'ENSTMO', 'ISABEE' ) );

	$groupes = array();
	$corps   = get_terms( array( 'taxonomy' => 'ueb_corps', 'hide_empty' => false, 'orderby' => 'term_id' ) );
	foreach ( is_array( $corps ) ? $corps : array() as $t ) {
		$membres = array();
		foreach ( get_posts( array( 'post_type' => 'ueb_personnel', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'tax_query' => array( array( 'taxonomy' => 'ueb_corps', 'terms' => $t->term_id ) ) ) ) as $p ) {
			$membres[] = sueb_membre_personnel( $p, $etabs, $t->name );
		}
		usort( $membres, function ( $a, $b ) use ( $rang ) {
			return array( $a['ordre'], $rang[ strtoupper( $a['etab'] ) ] ?? 99 ) <=> array( $b['ordre'], $rang[ strtoupper( $b['etab'] ) ] ?? 99 );
		} );
		$groupes[] = array( 'nom' => $t->name, 'slug' => $t->slug, 'membres' => $membres );
	}
	/* Les catégories du thème d'abord, dans leur ordre (la direction en tête), puis celles ajoutées dans l'admin. */
	$ordre = array_flip( array_keys( sueb_corps_personnel() ) );
	usort( $groupes, function ( $a, $b ) use ( $ordre ) {
		return ( $ordre[ $a['slug'] ] ?? 99 ) <=> ( $ordre[ $b['slug'] ] ?? 99 );
	} );
	return $groupes;
}

/** La fiche d'un membre du personnel, avec son établissement (sigle, nom, couleur). */
function sueb_membre_personnel( $p, array $etabs = array(), $corps = '' ) {
	$p    = get_post( $p );
	$etab = (int) sueb_meta( $p->ID, 'etablissement' );
	$e    = $etabs[ $etab ] ?? ( $etab ? array( 'sigle' => sueb_meta( $etab, 'sigle' ) ?: get_the_title( $etab ), 'nom' => get_the_title( $etab ), 'couleur' => sueb_meta( $etab, 'couleur' ) ?: ( sueb_etablissements()[ strtoupper( (string) sueb_meta( $etab, 'sigle' ) ) ]['couleur'] ?? '#163d2b' ) ) : array( 'sigle' => 'UEb', 'nom' => SUEB_UNIVERSITE['fr'], 'couleur' => '#163d2b' ) );
	if ( ! $corps ) {
		$termes = get_the_terms( $p, 'ueb_corps' );
		$corps  = $termes && ! is_wp_error( $termes ) ? $termes[0]->name : '';
	}
	return array(
		'id'       => $p->ID,
		'nom'      => $p->post_title,
		'a_nommer' => (bool) sueb_meta( $p->ID, 'demo' ) || 0 === stripos( $p->post_title, 'Nom à renseigner' ),
		'fonction' => sueb_meta( $p->ID, 'fonction' ),
		'cours'    => sueb_meta( $p->ID, 'cours' ),
		'courriel' => sueb_meta( $p->ID, 'courriel' ),
		'etab'     => $e['sigle'],
		'etab_id'  => $etab,
		'etab_nom' => $e['nom'],
		'couleur'  => $e['couleur'],
		'corps'    => $corps,
		'photo'    => sueb_image_url( $p->ID, 'sueb-portrait' ),
		'url'      => get_permalink( $p ),
		'ordre'    => (int) $p->menu_order,
		/* Le CV. */
		'grade'        => (string) sueb_meta( $p->ID, 'grade' ),
		'departement'  => (int) sueb_meta( $p->ID, 'departement' ) ? get_the_title( (int) sueb_meta( $p->ID, 'departement' ) ) : '',
		'specialite'   => (string) sueb_meta( $p->ID, 'specialite' ),
		'recherche'    => array_values( array_filter( array_map( 'trim', explode( ',', (string) sueb_meta( $p->ID, 'recherche' ) ) ) ) ),
		'diplomes'     => sueb_lignes_datees( sueb_meta( $p->ID, 'diplomes' ) ),
		'parcours'     => sueb_lignes_datees( sueb_meta( $p->ID, 'parcours' ) ),
		'publications' => array_values( array_filter( array_map( 'trim', preg_split( '/\R/u', (string) sueb_meta( $p->ID, 'publications' ) ) ) ) ),
		'cv'           => (int) sueb_meta( $p->ID, 'cv' ) ? (string) wp_get_attachment_url( (int) sueb_meta( $p->ID, 'cv' ) ) : '',
		'biographie'   => trim( (string) $p->post_content ),
	);
}

/** Lignes « 2014 : Doctorat… » ou « 2018-2022 : Poste… » d'un CV, découpées en date et texte. */
function sueb_lignes_datees( $texte ) {
	$lignes = array();
	foreach ( preg_split( '/\R/u', (string) $texte ) as $l ) {
		$l = trim( $l );
		if ( '' === $l ) {
			continue;
		}
		if ( preg_match( '/^([0-9][0-9\s\-–àaujourd’huiepuis]*?)\s*:\s*(.+)$/u', $l, $m ) ) {
			$lignes[] = array( 'date' => trim( $m[1] ), 'texte' => trim( $m[2] ) );
		} else {
			$lignes[] = array( 'date' => '', 'texte' => $l );
		}
	}
	return $lignes;
}

/** Le CV d'un membre du personnel : domaines, formation, parcours, enseignements, publications, biographie. */
function sueb_cv_personnel( array $m ) {
	$cours = array_values( array_filter( array_map( 'trim', explode( ',', (string) $m['cours'] ) ) ) );
	$vide  = ! $m['recherche'] && ! $m['diplomes'] && ! $m['parcours'] && ! $cours && ! $m['publications'] && ! $m['biographie'];
	ob_start();
	?>
	<div class="cv" data-cv>
		<?php if ( $vide ) : ?>
			<p class="cv__vide">Le CV de ce membre du personnel sera bientôt publié.</p>
		<?php endif; ?>
		<?php if ( $m['recherche'] || $m['specialite'] ) : ?>
			<section class="cv__bloc">
				<h2 class="cv__titre">Domaines de recherche</h2>
				<?php if ( $m['specialite'] ) : ?><p class="cv__specialite">Spécialité : <?php echo esc_html( $m['specialite'] ); ?></p><?php endif; ?>
				<?php if ( $m['recherche'] ) : ?><ul class="cv__etiquettes"><?php foreach ( $m['recherche'] as $r ) : ?><li><?php echo esc_html( $r ); ?></li><?php endforeach; ?></ul><?php endif; ?>
			</section>
		<?php endif; ?>
		<?php foreach ( array( 'parcours' => 'Parcours professionnel', 'diplomes' => 'Formation et diplômes' ) as $cle => $titre ) : if ( ! $m[ $cle ] ) { continue; } ?>
			<section class="cv__bloc">
				<h2 class="cv__titre"><?php echo esc_html( $titre ); ?></h2>
				<ol class="cv__frise">
					<?php foreach ( $m[ $cle ] as $l ) : ?>
						<li><?php if ( $l['date'] ) : ?><span class="cv__date"><?php echo esc_html( $l['date'] ); ?></span><?php endif; ?><span class="cv__texte"><?php echo esc_html( $l['texte'] ); ?></span></li>
					<?php endforeach; ?>
				</ol>
			</section>
		<?php endforeach; ?>
		<?php if ( $cours ) : ?>
			<section class="cv__bloc">
				<h2 class="cv__titre">Enseignements</h2>
				<ul class="cv__etiquettes cv__etiquettes--cours"><?php foreach ( $cours as $c ) : ?><li><?php echo sueb_icone( 'livre', 15 ); ?><?php echo esc_html( $c ); ?></li><?php endforeach; ?></ul>
			</section>
		<?php endif; ?>
		<?php if ( $m['publications'] ) : ?>
			<section class="cv__bloc">
				<h2 class="cv__titre">Publications choisies</h2>
				<ol class="cv__publications"><?php foreach ( $m['publications'] as $pub ) : ?><li><?php echo esc_html( $pub ); ?></li><?php endforeach; ?></ol>
			</section>
		<?php endif; ?>
		<?php if ( $m['biographie'] ) : ?>
			<section class="cv__bloc">
				<h2 class="cv__titre">Biographie</h2>
				<div class="prose cv__bio"><?php echo wp_kses_post( apply_filters( 'the_content', $m['biographie'] ) ); ?></div>
			</section>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/** Le portrait d'un membre : sa photo, sinon un aplat de la couleur de son établissement avec ses initiales (ou une silhouette si le nom manque). */
function sueb_portrait_personnel( array $m, $chargement = 'lazy' ) {
	if ( $m['photo'] ) {
		return sprintf( '<img src="%s" alt="" width="600" height="750" loading="%s" decoding="async">', esc_url( $m['photo'] ), esc_attr( $chargement ) );
	}
	$dedans = $m['a_nommer']
		? '<svg class="profil__silhouette" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8.5" r="4.2" fill="currentColor"/><path d="M3.6 21.5c.9-4.6 4.2-7.2 8.4-7.2s7.5 2.6 8.4 7.2z" fill="currentColor"/></svg>'
		: '<span class="profil__initiales">' . esc_html( sueb_initiales_personne( $m['nom'] ) ) . '</span>';
	return '<span class="profil__vide" aria-hidden="true">' . $dedans . '</span>';
}

/** Initiales d'une personne : prénom et nom (« Pr Marie-Claire Ondoua » donne MO). */
function sueb_initiales_personne( $nom ) {
	$mots = preg_split( '/\s+/u', trim( preg_replace( '/^(Pr\.?|Prof\.?|Dr\.?|M\.|Mme|Mlle)\s+/u', '', trim( $nom ) ) ), -1, PREG_SPLIT_NO_EMPTY );
	if ( count( $mots ) < 2 ) {
		return sueb_initiales( $nom );
	}
	return mb_strtoupper( mb_substr( $mots[0], 0, 1 ) . mb_substr( end( $mots ), 0, 1 ) );
}

/** La carte d'un membre dans l'annuaire : portrait, établissement, nom, fonction ; elle mène à sa fiche. */
function sueb_carte_personnel( array $m, $classe = '', $chargement = 'lazy' ) {
	$recherche = remove_accents( mb_strtolower( implode( ' ', array( $m['nom'], $m['fonction'], $m['cours'], $m['etab'], $m['etab_nom'], $m['grade'] ?? '', $m['specialite'] ?? '', $m['departement'] ?? '' ) ) ) );
	ob_start();
	?>
	<li class="<?php echo esc_attr( trim( 'profil ' . $classe ) ); ?>" style="--etab:<?php echo esc_attr( $m['couleur'] ); ?>;--sur-etab:<?php echo esc_attr( sueb_texte_sur( $m['couleur'] ) ); ?>" data-etab="<?php echo esc_attr( $m['etab'] ); ?>" data-recherche="<?php echo esc_attr( $recherche ); ?>">
		<a class="profil__lien" href="<?php echo esc_url( $m['url'] ); ?>" data-cv-lien>
			<span class="profil__portrait">
				<?php echo sueb_portrait_personnel( $m, $chargement ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans la fonction ?>
				<span class="profil__etab"><img src="<?php echo esc_url( sueb_logo( $m['etab'] ) ); ?>" alt="" width="20" height="20" loading="lazy"><?php echo esc_html( $m['etab'] ); ?></span>
			</span>
			<strong class="profil__nom<?php echo $m['a_nommer'] ? ' profil__nom--attente' : ''; ?>"><?php echo esc_html( $m['nom'] ); ?></strong>
			<span class="profil__fonction"><?php echo esc_html( $m['fonction'] ); ?></span>
			<?php if ( ! empty( $m['grade'] ) && false === stripos( (string) $m['fonction'], $m['grade'] ) ) : ?><span class="profil__grade"><?php echo esc_html( $m['grade'] ); ?></span><?php endif; ?>
		</a>
	</li>
	<?php
	return ob_get_clean();
}

/**
 * Les diapositives de la bannière : les dernières actualités et les
 * prochains évènements, en alternance (actualité, évènement, actualité…).
 * Quand il y a moins de trois contenus, la bannière est complétée par des
 * diapositives d'établissements (et, s'il n'y a rien du tout, par l'accueil
 * du Customizer en premier) pour qu'elle défile toujours.
 */
function sueb_slides_banniere( $nombre = 5, array $arbre = array() ) {
	$repli_photos = array( 'campus-ebolowa', 'remise-toges', 'amphi', 'bibliotheque', 'vie-etudiante-1' );

	$actus = array();
	foreach ( get_posts( array( 'post_type' => 'post', 'numberposts' => $nombre ) ) as $p ) {
		$format = get_post_format( $p );
		$actus[] = array(
			'type'      => 'actualite',
			'etiquette' => 'video' === $format ? 'Vidéo' : ( 'audio' === $format ? 'Audio' : 'Actualité' ),
			'titre'     => $p->post_title,
			'texte'     => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $p ) ), 32 ),
			'url'       => get_permalink( $p ),
			'lien'      => 'video' === $format ? 'Voir la vidéo' : ( 'audio' === $format ? 'Écouter' : 'Lire la suite' ),
			'date'      => get_the_date( 'j F Y', $p ),
			'iso'       => get_the_date( 'c', $p ),
			'lieu'      => '',
			'photo'     => sueb_image_url( $p->ID, 'sueb-banniere' ) ?: sueb_repli_article(),
		);
	}

	/* Évènements à venir seulement : un évènement passé n'a rien à faire à la une. */
	$evts = array();
	$prochains = get_posts( array(
		'post_type'   => 'ueb_evenement',
		'numberposts' => 2,
		'meta_key'    => '_sueb_debut',
		'orderby'     => 'meta_value',
		'order'       => 'ASC',
		'meta_query'  => array( array( 'key' => '_sueb_debut', 'value' => current_time( 'Y-m-d\TH:i' ), 'compare' => '>=' ) ),
	) );
	foreach ( $prochains as $p ) {
		/* La date saisie est celle du site : on la lit dans son fuseau, sans décalage. */
		$dt = date_create_immutable( (string) sueb_meta( $p->ID, 'debut' ), wp_timezone() );
		$ts = $dt ? $dt->getTimestamp() : 0;
		$evts[] = array(
			'type'      => 'evenement',
			'etiquette' => 'Évènement',
			'titre'     => $p->post_title,
			'texte'     => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $p ) ), 32 ),
			'url'       => get_permalink( $p ),
			'lien'      => 'En savoir plus',
			'date'      => $ts ? wp_date( 'j F Y', $ts ) . ' · ' . wp_date( 'G\hi', $ts ) : '',
			'iso'       => $ts ? wp_date( 'c', $ts ) : '',
			'lieu'      => (string) sueb_meta( $p->ID, 'lieu' ),
			'photo'     => sueb_image_url( $p->ID, 'sueb-banniere' ),
		);
	}

	$liste = array();
	for ( $i = 0; count( $liste ) < $nombre && ( $actus || $evts ); $i++ ) {
		if ( 1 === $i % 2 && $evts ) {
			$liste[] = array_shift( $evts );
		} elseif ( $actus ) {
			$liste[] = array_shift( $actus );
		} else {
			$liste[] = array_shift( $evts );
		}
	}

	if ( count( $liste ) < 3 ) {
		if ( ! $liste ) {
			$liste[] = array(
				'type'      => 'accueil',
				'etiquette' => sueb_reglage( 'accueil_surtitre' ),
				'titre'     => sueb_reglage( 'accueil_titre' ),
				'texte'     => sueb_reglage( 'accueil_texte' ),
				'url'       => '#etablissements',
				'lien'      => 'Nos établissements',
				'date'      => '',
				'iso'       => '',
				'lieu'      => '',
				'photo'     => '',
			);
		}
		$fixe = sueb_etablissements();
		foreach ( $arbre as $e ) {
			if ( count( $liste ) >= $nombre ) {
				break;
			}
			$repli   = $fixe[ strtoupper( $e['sigle'] ) ]['photos'][0] ?? 'campus-ebolowa';
			$liste[] = array(
				'type'      => 'etablissement',
				'etiquette' => 'Établissement',
				'titre'     => $e['titre'],
				'texte'     => wp_trim_words( $e['texte'], 32 ) ?: 'Découvrez ses départements, ses filières et ses unités d’enseignement.',
				'url'       => $e['url'],
				'lien'      => 'Découvrir l’établissement',
				'date'      => '',
				'iso'       => '',
				'lieu'      => $e['ville'],
				'photo'     => sueb_image_url( $e['id'], 'sueb-banniere', '', $repli ),
			);
		}
	}

	foreach ( $liste as $i => &$s ) {
		if ( ! $s['photo'] ) {
			$s['photo'] = sueb_photo( $repli_photos[ $i % count( $repli_photos ) ] );
		}
	}
	unset( $s );
	return $liste;
}