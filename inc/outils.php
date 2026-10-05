<?php
/**
 * Petits outils partagés : icônes, images, méta.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/** URL d'une photo livrée avec le thème (assets/images/photos). */
function sueb_photo( $nom ) {
	$ext = file_exists( SUEB_DIR . "/assets/images/photos/$nom.webp" ) ? 'webp' : 'jpg';
	return SUEB_URI . "/assets/images/photos/$nom.$ext";
}

/**
 * Nom de la photo de repli des articles sans image mise en avant : l'image par
 * défaut de l'UEb (assets/images/photos/article-par-defaut.jpg ou .webp) si
 * elle est déposée, sinon une photo du campus.
 */
function sueb_repli_article() {
	foreach ( array( 'webp', 'jpg' ) as $ext ) {
		if ( file_exists( SUEB_DIR . "/assets/images/photos/article-par-defaut.$ext" ) ) {
			return 'article-par-defaut';
		}
	}
	return 'campus-ebolowa';
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
		'guillemet' => '<path d="M10 7H6a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h3v1a3 3 0 0 1-3 3M20 7h-4a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h3v1a3 3 0 0 1-3 3"/>',
	);
	if ( empty( $traces[ $nom ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="icone icone--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $nom ), (int) $taille, $traces[ $nom ]
	);
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