<?php
/**
 * Petits outils partagés : icônes, images, méta.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/** URL d'une photo livrée avec le thème (assets/images/photos). */
function sueb_photo( $nom ) {
	if ( false !== strpos( $nom, '://' ) ) {
		return $nom; // déjà une adresse complète (ex. l'image par défaut des articles)
	}
	$ext = file_exists( SUEB_DIR . "/assets/images/photos/$nom.webp" ) ? 'webp' : 'jpg';
	return SUEB_URI . "/assets/images/photos/$nom.$ext";
}

/**
 * URL de l'image par défaut des articles sans image mise en avant. On la cherche
 * dans assets/images/photos/ puis assets/images/, sous les noms article-par-defaut,
 * ueb-article-par-defaut ou ueb-default-article (.webp, .jpg, .jpeg ou .png).
 * Sans fichier déposé, on affiche une photo du campus.
 */
function sueb_repli_article() {
	$noms = array( 'article-par-defaut', 'ueb-article-par-defaut', 'ueb-default-article' );
	foreach ( array( 'photos/', '' ) as $dossier ) {
		foreach ( $noms as $nom ) {
			foreach ( array( 'webp', 'jpg', 'jpeg', 'png' ) as $ext ) {
				if ( file_exists( SUEB_DIR . "/assets/images/$dossier$nom.$ext" ) ) {
					return SUEB_URI . "/assets/images/$dossier$nom.$ext";
				}
			}
		}
	}
	return sueb_photo( 'campus-ebolowa' );
}

/** Photo d'étudiant(e) d'un établissement (assets/images/etudiants/<sigle>.webp ou .png), ou chaîne vide. */
function sueb_photo_etudiant( $sigle, $id = 0 ) {
	/* La photo choisie dans la gestion (champ photo_etudiant de l'établissement), sinon celle du thème. */
	$media = $id ? (int) get_post_meta( (int) $id, '_sueb_photo_etudiant', true ) : 0;
	$url   = $media ? (string) wp_get_attachment_image_url( $media, 'large' ) : '';
	if ( $url ) {
		return $url;
	}
	$sigle = strtolower( $sigle );
	foreach ( array( 'webp', 'png' ) as $ext ) {
		if ( file_exists( SUEB_DIR . "/assets/images/etudiants/$sigle.$ext" ) ) {
			return SUEB_URI . "/assets/images/etudiants/$sigle.$ext";
		}
	}
	return '';
}

/** Couleur de texte lisible (blanc, ou vert très sombre sur les fonds clairs) pour un fond hexadécimal. */
function sueb_texte_sur( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		return '#ffffff';
	}
	$c   = array_map(
		function ( $v ) {
			$v = hexdec( $v ) / 255;
			return $v <= 0.03928 ? $v / 12.92 : pow( ( $v + 0.055 ) / 1.055, 2.4 );
		},
		str_split( $hex, 2 )
	);
	$lum = 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
	return ( 1.05 / ( $lum + 0.05 ) ) >= 4.5 ? '#ffffff' : '#0f2c1f';
}

/** URL du logo d'un établissement par son sigle, ou de l'université. */
function sueb_logo( $sigle = 'UEB' ) {
	$fichier = strtolower( $sigle ) . '.png';
	return file_exists( SUEB_DIR . '/assets/images/logos/' . $fichier ) ? SUEB_URI . '/assets/images/logos/' . $fichier : SUEB_URI . '/assets/images/logos/ueb.png';
}

/** Une méta du thème (préfixe _sueb_). */
function sueb_meta( $post_id, $cle ) {
	return get_post_meta( $post_id, '_sueb_' . $cle, true );
}

/**
 * URL d'une image : pièce jointe (ID) de la méta, sinon image mise en
 * avant, sinon photo de repli du thème.
 */
function sueb_image_url( $post_id, $taille = 'large', $meta = '', $repli = '' ) {
	$id = $meta ? (int) sueb_meta( $post_id, $meta ) : (int) get_post_thumbnail_id( $post_id );
	if ( $id && ( $src = wp_get_attachment_image_url( $id, $taille ) ) ) {
		return $src;
	}
	return $repli ? sueb_photo( $repli ) : '';
}

/** Initiales d'un nom, pour les portraits sans photo. */
function sueb_initiales( $nom ) {
	$nom   = preg_replace( '/^(Pr\.?|Prof\.?|Dr\.?|M\.|Mme)\s+/u', '', trim( $nom ) );
	$mots  = preg_split( '/[\s\-]+/u', $nom, -1, PREG_SPLIT_NO_EMPTY );
	$init  = '';
	foreach ( array_slice( $mots, 0, 2 ) as $m ) {
		$init .= mb_strtoupper( mb_substr( $m, 0, 1 ) );
	}
	return $init ?: 'UEb';
}

/** Icônes en ligne (trait de 1,75 px, héritent de la couleur du texte). */
function sueb_icone( $nom, $taille = 20 ) {
	$traces = array(
		'fleche'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'chevron'   => '<path d="m6 9 6 6 6-6"/>',
		'droite'    => '<path d="m9 6 6 6-6 6"/>',
		'gauche'    => '<path d="m15 6-6 6 6 6"/>',
		'lecture'   => '<path d="M7 4.5v15l12-7.5z" fill="currentColor"/>',
		'video'     => '<rect x="3" y="5" width="13" height="14" rx="2"/><path d="m16 10 5-3v10l-5-3"/>',
		'audio'     => '<path d="M3 14v-2a9 9 0 0 1 18 0v2"/><rect x="3" y="14" width="4" height="6" rx="1.5"/><rect x="17" y="14" width="4" height="6" rx="1.5"/>',
		'article'   => '<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h5"/>',
		'calendrier'=> '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
		'lieu'      => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'recherche' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
		'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'fermer'    => '<path d="M6 6l12 12M18 6 6 18"/>',
		'telephone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
		'courriel'  => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'livre'     => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 19V5M8 7h7"/>',
		'document'  => '<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5"/><path d="M12 11v6M9 14l3 3 3-3"/>',
		'externe'   => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
		'personnes' => '<circle cx="9" cy="8" r="3.5"/><path d="M3 20a6 6 0 0 1 12 0M16 4.5a3.5 3.5 0 0 1 0 7M21 20a6 6 0 0 0-4-5.6"/>',
		'facebook'  => '<path d="M14 21v-8h3l.5-3.5H14V7.8c0-1 .3-1.8 1.8-1.8h1.8V3a24 24 0 0 0-2.7-.1c-2.7 0-4.4 1.6-4.4 4.5v2.1h-3V13h3v8"/>',
		'youtube'   => '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="m10 9.2 5 2.8-5 2.8z" fill="currentColor"/>',
		'guillemet' => '<path d="M10 7H6a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h3v1a3 3 0 0 1-3 3M20 7h-4a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h3v1a3 3 0 0 1-3 3"/>',
		'horloge'   => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
		'lien'      => '<path d="M10 14a4.5 4.5 0 0 0 6.4 0l3-3a4.5 4.5 0 0 0-6.4-6.4l-1 1M14 10a4.5 4.5 0 0 0-6.4 0l-3 3a4.5 4.5 0 0 0 6.4 6.4l1-1"/>',
		'coche'     => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
		'toque'     => '<path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11.2V16c0 1.4 2.7 3 6 3s6-1.6 6-3v-4.8M22 9v6"/>',
		'mallette'  => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18M12 12v2"/>',
		'engrenage' => '<circle cx="12" cy="12" r="3"/><path d="M9.7 5.2L10.2 2.7L13.8 2.7L14.3 5.2L15.2 5.5L17.3 4.1L19.9 6.7L18.5 8.8L18.8 9.7L21.3 10.2L21.3 13.8L18.8 14.3L18.5 15.2L19.9 17.3L17.3 19.9L15.2 18.5L14.3 18.8L13.8 21.3L10.2 21.3L9.7 18.8L8.8 18.5L6.7 19.9L4.1 17.3L5.5 15.2L5.2 14.3L2.7 13.8L2.7 10.2L5.2 9.7L5.5 8.8L4.1 6.7L6.7 4.1L8.8 5.5z"/>',
		'sante'     => '<path d="M9.5 3h5v6.5H21v5h-6.5V21h-5v-6.5H3v-5h6.5z"/>',
		'partager'  => '<circle cx="18" cy="5.5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="18.5" r="2.5"/><path d="m8.2 10.8 7.6-4M8.2 13.2l7.6 4"/>',
		'whatsapp'  => '<path d="M4 20l1.2-4.1A8 8 0 1 1 8.3 19z"/><path d="M9.2 8.6c.2-.5.5-.5.8-.5h.5c.2 0 .4 0 .5.4l.7 1.6c.1.2 0 .4-.1.6l-.5.6c-.1.1-.2.3 0 .5.3.6.8 1.2 1.3 1.6.6.5 1.1.7 1.5.9.2.1.4 0 .5-.1l.7-.8c.2-.2.3-.2.5-.1l1.6.8c.2.1.3.2.3.4 0 .5-.2 1.2-.8 1.5-.6.4-1.6.5-3.2-.2-1.9-.8-3.3-2.6-3.6-3.1-.4-.6-.9-1.6-.5-2.6z" fill="currentColor" stroke="none"/>',
		'linkedin'  => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 10.5v6M8 7.5v.01M12 16.5v-3.5a2 2 0 0 1 4 0v3.5M12 10.5v6"/>',
		/* Disciplines : une icône par département (voir sueb_icone_discipline()). */
		'fiole'     => '<path d="M9 3h6M10 3v6.5l-5.4 8.9A1.7 1.7 0 0 0 6 21h12a1.7 1.7 0 0 0 1.4-2.6L14 9.5V3"/><path d="M7.4 15h9.2"/>',
		'feuille'   => '<path d="M5 19C5 11 10 5 20 4c-1 10-7 15-15 15z"/><path d="M5 19c3-4 6-6.5 9-8"/>',
		'atome'     => '<circle cx="12" cy="12" r="1.4"/><ellipse cx="12" cy="12" rx="9.5" ry="3.8"/><ellipse cx="12" cy="12" rx="9.5" ry="3.8" transform="rotate(60 12 12)"/><ellipse cx="12" cy="12" rx="9.5" ry="3.8" transform="rotate(120 12 12)"/>',
		'ecran'     => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
		'courbe'    => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/>',
		'eclair'    => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
		'montagne'  => '<path d="M2 20 9 7l4 7 3-4 6 10z"/>',
		'pousse'    => '<path d="M12 21v-9M7 21h10"/><path d="M12 12c0-4 3-7 8-7 0 4-3 7-8 7zM12 14c0-3-2.5-5.5-7-5.5 0 3 2.5 5.5 7 5.5z"/>',
		'batiment'  => '<rect x="5" y="3" width="14" height="18" rx="1.5"/><path d="M9 7h1M14 7h1M9 11h1M14 11h1M9 15h1M14 15h1M11 21v-3h2v3"/>',
		'cle'       => '<path d="M15 4a5 5 0 0 0-4.6 6.9l-6.7 6.7a1.9 1.9 0 0 0 2.7 2.7l6.7-6.7A5 5 0 0 0 20 9l-3 3-3-1-1-3z"/>',
		'arbre'     => '<path d="M12 3 6 11h3l-4 6h14l-4-6h3z"/><path d="M12 17v4"/>',
		'carte'     => '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3z"/><path d="M9 3v15M15 6v15"/>',
		'camion'    => '<path d="M3 6h11v10H3zM14 9h4l3 3.5V16h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
		'panier'    => '<path d="M3 4h2l2.4 11h11l2-8H6.2"/><circle cx="9" cy="19.5" r="1.3"/><circle cx="17" cy="19.5" r="1.3"/>',
		'goutte'    => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"/>',
		'nuage'     => '<path d="M7 18a4 4 0 0 1-.6-8A6 6 0 0 1 18 9a4.5 4.5 0 0 1 0 9z"/>',
		'globe'     => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z"/>',
		'ancre'     => '<circle cx="12" cy="5" r="2"/><path d="M12 7v14M8 11h8M4 14a8 8 0 0 0 16 0"/>',
	);
	if ( empty( $traces[ $nom ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="icone icone--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $nom ), (int) $taille, $traces[ $nom ]
	);
}

/** L'icône d'un département d'après son intitulé : « Chimie appliquée » donne la fiole, « Génie civil » le bâtiment. */
function sueb_icone_discipline( $nom ) {
	$motifs = array( // dans l'ordre : le premier qui convient l'emporte
		'/biom[ée]dical|sant[ée]|m[ée]decine|pharmacie/iu'                       => 'sante',
		'/agricult|[ée]levage|rural|halieut/iu'                                  => 'pousse',
		'/biolog|pharmacognosie|biotech/iu'                                      => 'feuille',
		'/chimie/iu'                                                             => 'fiole',
		'/physique|sciences? (?:fondamentales|de base)|scientifiques de base/iu' => 'atome',
		'/informati|information|num[ée]rique/iu'                                 => 'ecran',
		'/commerc|marketing/iu'                                                  => 'panier',
		'/[ée]nergie|[ée]lectri/iu'                                              => 'eclair',
		'/g[ée]oscience|g[ée]olog|mine/iu'                                       => 'montagne',
		'/recherche op[ée]rationnelle|[ée]conom|statisti|math[ée]mati|gestion/iu' => 'courbe',
		'/bois|for[êe]t|foresterie/iu'                                           => 'arbre',
		'/civil|habitat|b[âa]timent|construction/iu'                             => 'batiment',
		'/m[ée]cani|m[ée]catroni|industri|ing[ée]nieur/iu'                       => 'engrenage',
		'/maintenance/iu'                                                        => 'cle',
		'/g[ée]omati|topograph|g[ée]ograph/iu'                                   => 'carte',
		'/tourisme|h[ôo]tel|professionnel/iu'                                    => 'mallette',
		'/transport|logisti/iu'                                                  => 'camion',
		'/\beaux?\b|hydraul/iu'                                                  => 'goutte',
		'/m[ée]t[ée]orolog|climat/iu'                                            => 'nuage',
		'/environnement/iu'                                                      => 'globe',
		'/maritime|oc[ée]an|naval|portuaire/iu'                                  => 'ancre',
		'/doctora/iu'                                                            => 'toque',
	);
	foreach ( $motifs as $motif => $icone ) {
		if ( preg_match( $motif, $nom ) ) {
			return $icone;
		}
	}
	return 'livre';
}

/** Date courte en français : « 18 nov. 2024 ». */
function sueb_date( $date, $format = 'j M Y' ) {
	$ts = is_numeric( $date ) ? (int) $date : strtotime( (string) $date );
	return $ts ? wp_date( $format, $ts ) : '';
}

/** Une URL de vidéo YouTube ou Vimeo devient une URL d'intégration. */
function sueb_video_integree( $url ) {
	if ( preg_match( '~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', $url, $m ) ) {
		return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0';
	}
	if ( preg_match( '~vimeo\.com/(\d+)~', $url, $m ) ) {
		return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1';
	}
	return '';
}