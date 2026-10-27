<?php
/**
 * Champs complémentaires de l'admin, déclarés par type de contenu.
 * Chaque champ est une méta « _sueb_<cle> ». Les champs « media »
 * stockent l'ID de la pièce jointe (photo, PDF, vidéo, audio).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

function sueb_champs() {
	$fonctions = array( '' => '— Choisir —' );
	foreach ( sueb_fonctions_rectorat() as $sigle => $titre ) {
		$fonctions[ $sigle ] = $sigle . ' — ' . $titre;
	}

	$villes = array( '' => '— Hors carte —' );
	foreach ( array_keys( sueb_villes() ) as $v ) {
		$villes[ $v ] = $v;
	}

	return array(
		'ueb_dirigeant'  => array(
			'titre'  => 'Fonction au rectorat',
			'champs' => array(
				'sigle'    => array( 'Fonction', 'select', $fonctions ),
				'fonction' => array( 'Intitulé affiché', 'text', 'Laisser vide pour l’intitulé officiel de la fonction choisie.' ),
			),
		),
		'ueb_structure'  => array(
			'titre'  => 'Fiche de la structure',
			'aide'   => 'Le niveau dépend du rattachement (« Attributs » à droite) : sans parent = établissement, puis département, filière, unité d’enseignement.',
			'champs' => array(
				'sigle'        => array( 'Sigle', 'text', 'Établissement ou département (ex. FS, MATH).' ),
				'ville'        => array( 'Ville', 'text', 'Établissement seulement.' ),
				'couleur'      => array( 'Couleur', 'color', 'Établissement seulement : couleur de sa charte.' ),
				'logo'         => array( 'Logo', 'media', 'Établissement seulement. Sinon, le logo livré avec le thème.' ),
				'photo_droite' => array( 'Seconde photo', 'media', 'Photo de l’extrémité droite dans la liste (la photo de gauche est l’image mise en avant).' ),
				'responsable'  => array( 'Responsable', 'text', 'Doyen, directeur, chef de département ou coordonnateur.' ),
				'diplome'      => array( 'Diplôme délivré', 'text', 'Filière seulement (ex. Licence, Master).' ),
				'niveaux'      => array( 'Niveaux ouverts', 'text', 'Filière seulement, séparés par des virgules (ex. L1, L2, L3, M1).' ),
				'code'         => array( 'Code de l’UE', 'text', 'UE seulement (ex. MAT 111).' ),
				'credits'      => array( 'Crédits', 'number', 'UE seulement.' ),
				'semestre'     => array( 'Semestre', 'text', 'UE seulement (ex. S1).' ),
				'volume'       => array( 'Volume horaire', 'text', 'UE seulement (ex. 60 h : 30 CM, 20 TD, 10 TP).' ),
				'syllabus'     => array( 'Syllabus (PDF)', 'media', 'UE seulement.' ),
			),
		),
		'ueb_partenaire' => array(
			'titre'  => 'Convention',
			'champs' => array(
				'logo'      => array( 'Logo du partenaire', 'media', '' ),
				'type'      => array( 'Type de partenaire', 'text', 'Ex. Entreprise publique, Institut de recherche, Ministère.' ),
				'ville'     => array( 'Ville (pour la carte)', 'select', $villes ),
				'sigle'     => array( 'Sigle', 'text', 'Facultatif, affiché quand il n’y a pas de logo (ex. MINTOUL). Sinon, tiré du nom.' ),
				'tutelle'   => array( 'Tutelle à l’UEb', 'text', 'Pour un établissement sous tutelle académique : le ou les établissements de l’UEb qui l’assurent (ex. ESTLC ; FS, FSEG, FSJP).' ),
				'filieres'  => array( 'Filières sous tutelle', 'textarea', 'Une filière par ligne, précédée du sigle de l’établissement de l’UEb qui en assure la tutelle (ex. « FSEG : Banque »). Elle apparaît alors dans la section Établissements.' ),
				'signature' => array( 'Date de signature', 'date', '' ),
				'lien'      => array( 'Lien vers l’article de presse', 'url', '' ),
			),
		),
		'ueb_evenement'  => array(
			'titre'  => 'Date et lieu',
			'champs' => array(
				'debut' => array( 'Début', 'datetime-local', '' ),
				'fin'   => array( 'Fin', 'datetime-local', 'Facultatif.' ),
				'lieu'  => array( 'Lieu', 'text', 'Ex. Amphithéâtre 500, campus d’Ebolowa.' ),
			),
		),
		'ueb_personnel'  => array(
			'titre'  => 'Fiche du personnel',
			'champs' => array(
				'fonction'      => array( 'Fonction', 'text', 'Ex. Doyen, Chef du département de Mathématiques, Maître de conférences.' ),
				'etablissement' => array( 'Établissement', 'etablissement', '' ),
				'pole'          => array( 'Pôle (direction de l’université)', 'select', array( '' => '— Selon la fonction —' ) + sueb_poles_direction() ),
				'cours'       => array( 'Cours dispensé(s)', 'text', 'Séparer par des virgules.' ),
				'courriel'      => array( 'Courriel', 'email', 'Facultatif.' ),
				'grade'         => array( 'Grade', 'select', array( '' => '— Choisir —', 'Professeur' => 'Professeur', 'Maître de conférences' => 'Maître de conférences', 'Chargé de cours' => 'Chargé de cours', 'Assistant' => 'Assistant', 'Enseignant vacataire' => 'Enseignant vacataire', 'Personnel administratif' => 'Personnel administratif' ) ),
				'departement'   => array( 'Département', 'departement', 'Pour un chef de département ou un enseignant.' ),
				'specialite'    => array( 'Spécialité', 'text', 'Ex. Mathématiques appliquées, Droit des affaires.' ),
				'recherche'     => array( 'Domaines de recherche', 'text', 'Séparer par des virgules.' ),
				'diplomes'      => array( 'Formation et diplômes', 'textarea', 'Un diplôme par ligne, l’année d’abord : « 2014 : Doctorat en mathématiques, Université de Yaoundé I ».' ),
				'parcours'      => array( 'Parcours professionnel', 'textarea', 'Un poste par ligne, la période d’abord : « 2018-2022 : Chargé de cours, Université de Douala ».' ),
				'publications'  => array( 'Publications choisies', 'textarea', 'Une publication par ligne.' ),
				'cv'            => array( 'CV complet (PDF)', 'media', 'Facultatif : proposé au téléchargement sur la fiche.' ),
			),
		),
		'post'           => array(
			'titre'  => 'Média de l’actualité',
			'aide'   => 'Pour une vidéo ou un audio, choisir aussi le format correspondant dans le panneau « Format ».',
			'champs' => array(
				'media'     => array( 'Fichier vidéo ou audio', 'media', 'Envoyé dans la médiathèque (MP4, MP3…).' ),
				'media_url' => array( 'Ou lien YouTube / Vimeo', 'url', '' ),
				'duree'     => array( 'Durée', 'text', 'Ex. 3 min 20.' ),
			),
		),
	);
}

add_action( 'add_meta_boxes', function ( $type ) {
	$tous = sueb_champs();
	if ( empty( $tous[ $type ] ) ) {
		return;
	}
	add_meta_box( 'sueb-champs', $tous[ $type ]['titre'], 'sueb_boite_champs', $type, 'normal', 'high', $tous[ $type ] );
} );

function sueb_boite_champs( $post, $boite ) {
	$def = $boite['args'];
	wp_nonce_field( 'sueb_champs', 'sueb_champs_nonce' );
	if ( ! empty( $def['aide'] ) ) {
		printf( '<p class="description" style="margin:0 0 12px">%s</p>', esc_html( $def['aide'] ) );
	}
	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( $def['champs'] as $cle => $c ) {
		list( $libelle, $type ) = $c;
		$extra = $c[2] ?? '';
		$nom   = 'sueb[' . $cle . ']';
		$val   = sueb_meta( $post->ID, $cle );
		$id    = 'sueb-' . $cle;
		printf( '<tr><th scope="row"><label for="%s">%s</label></th><td>', esc_attr( $id ), esc_html( $libelle ) );

		switch ( $type ) {
			case 'select':
				printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $nom ) );
				foreach ( $extra as $v => $l ) {
					printf( '<option value="%s"%s>%s</option>', esc_attr( $v ), selected( $val, $v, false ), esc_html( $l ) );
				}
				echo '</select>';
				$extra = '';
				break;

			case 'etablissement':
				$etabs = get_posts( array( 'post_type' => 'ueb_structure', 'post_parent' => 0, 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
				printf( '<select id="%s" name="%s"><option value="">— Rectorat / aucun —</option>', esc_attr( $id ), esc_attr( $nom ) );
				foreach ( $etabs as $e ) {
					printf( '<option value="%d"%s>%s</option>', $e->ID, selected( (int) $val, $e->ID, false ), esc_html( ( sueb_meta( $e->ID, 'sigle' ) ? sueb_meta( $e->ID, 'sigle' ) . ' — ' : '' ) . $e->post_title ) );
				}
				echo '</select>';
				break;

			case 'departement':
				printf( '<select id="%s" name="%s"><option value="">— Aucun —</option>', esc_attr( $id ), esc_attr( $nom ) );
				foreach ( get_posts( array( 'post_type' => 'ueb_structure', 'post_parent' => 0, 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) ) as $e ) {
					$deps = get_children( array( 'post_parent' => $e->ID, 'post_type' => 'ueb_structure', 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
					if ( ! $deps ) {
						continue;
					}
					printf( '<optgroup label="%s">', esc_attr( sueb_meta( $e->ID, 'sigle' ) ?: $e->post_title ) );
					foreach ( $deps as $d ) {
						printf( '<option value="%d"%s>%s</option>', $d->ID, selected( (int) $val, $d->ID, false ), esc_html( $d->post_title ) );
					}
					echo '</optgroup>';
				}
				echo '</select>';
				break;

			case 'media':
				$apercu = '';
				if ( $val ) {
					$apercu = wp_attachment_is_image( $val ) ? wp_get_attachment_image( $val, 'thumbnail', false, array( 'style' => 'max-width:96px;height:auto;display:block;margin-bottom:6px' ) ) : '<code>' . esc_html( basename( (string) get_attached_file( $val ) ) ) . '</code><br>';
				}
				printf(
					'<div class="sueb-media"><div class="sueb-media__apercu">%s</div><input type="hidden" id="%s" name="%s" value="%s"><button type="button" class="button sueb-media__choisir">Choisir</button> <button type="button" class="button-link-delete sueb-media__retirer"%s>Retirer</button></div>',
					$apercu, esc_attr( $id ), esc_attr( $nom ), esc_attr( $val ), $val ? '' : ' hidden'
				);
				break;

			case 'color':
				printf( '<input type="color" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $nom ), esc_attr( $val ?: '#0f2c1f' ) );
				break;

			case 'textarea':
				printf( '<textarea id="%s" name="%s" rows="8" class="large-text">%s</textarea>', esc_attr( $id ), esc_attr( $nom ), esc_textarea( $val ) );
				break;

			default:
				printf( '<input type="%s" id="%s" name="%s" value="%s" class="regular-text">', esc_attr( $type ), esc_attr( $id ), esc_attr( $nom ), esc_attr( $val ) );
		}

		if ( $extra ) {
			printf( '<p class="description">%s</p>', esc_html( $extra ) );
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

add_action( 'save_post', function ( $post_id, $post ) {
	if ( ! isset( $_POST['sueb_champs_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sueb_champs_nonce'] ), 'sueb_champs' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$tous = sueb_champs();
	if ( empty( $tous[ $post->post_type ] ) ) {
		return;
	}
	$recu = isset( $_POST['sueb'] ) ? wp_unslash( (array) $_POST['sueb'] ) : array();
	foreach ( $tous[ $post->post_type ]['champs'] as $cle => $c ) {
		$v = $recu[ $cle ] ?? '';
		switch ( $c[1] ) {
			case 'media':
			case 'etablissement':
			case 'departement':
			case 'number':
				$v = '' === $v ? '' : absint( $v );
				break;
			case 'url':
				$v = esc_url_raw( $v );
				break;
			case 'email':
				$v = sanitize_email( $v );
				break;
			case 'color':
				$v = sanitize_hex_color( $v );
				break;
			case 'textarea':
				$v = sanitize_textarea_field( $v );
				break;
			default:
				$v = sanitize_text_field( $v );
		}
		if ( '' === $v || null === $v ) {
			delete_post_meta( $post_id, '_sueb_' . $cle );
		} else {
			update_post_meta( $post_id, '_sueb_' . $cle, $v );
		}
	}
}, 10, 2 );

/* Sélecteur de la médiathèque pour les champs « media ». */
add_action( 'admin_enqueue_scripts', function ( $page ) {
	if ( ! in_array( $page, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script( 'media-editor', <<<'JS'
document.addEventListener('click', function (e) {
	var bloc = e.target.closest('.sueb-media');
	if (!bloc) return;
	var champ = bloc.querySelector('input'), apercu = bloc.querySelector('.sueb-media__apercu'), retirer = bloc.querySelector('.sueb-media__retirer');
	if (e.target.classList.contains('sueb-media__choisir')) {
		var cadre = wp.media({ title: 'Choisir un fichier', multiple: false });
		cadre.on('select', function () {
			var f = cadre.state().get('selection').first().toJSON();
			champ.value = f.id;
			apercu.innerHTML = f.type === 'image' ? '<img src="' + (f.sizes && f.sizes.thumbnail ? f.sizes.thumbnail.url : f.url) + '" style="max-width:96px;height:auto;display:block;margin-bottom:6px">' : '<code>' + f.filename + '</code><br>';
			retirer.hidden = false;
		});
		cadre.open();
	}
	if (e.target.classList.contains('sueb-media__retirer')) {
		champ.value = ''; apercu.innerHTML = ''; retirer.hidden = true;
	}
});
JS
	);
} );
